<?php
/**
 * Convierte la suite heredada de Kohana/Koseven (PHPUnit 4) a PHPUnit 11.
 *
 * Por que existe: PHPUnit 10+ exige que el nombre de la clase coincida con el
 * nombre del fichero, y los tests de 2016 se llaman igual en suites distintas
 * (FileTest en core y en cache). Asi que cada fichero se renombra con un prefijo
 * de suite y estable, y todas las referencias cruzadas se reescriben.
 *
 * Las assertions que PHPUnit elimino (assertTag, assertAttributeSame...) NO se
 * tocan aqui: las aporta KO8_TestCase con la misma semantica.
 *
 * Uso: php tools/import-legacy-tests.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$dest = $root.'/tests/legacy';

/**
 * Copy a directory tree.
 */
function ko8_copy(string $from, string $to): void
{
	if ( ! is_dir($to))
	{
		mkdir($to, 0777, TRUE);
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator AS $item)
	{
		$target = $to.DIRECTORY_SEPARATOR.$iterator->getSubPathname();

		if ($item->isDir())
		{
			if ( ! is_dir($target))
			{
				mkdir($target, 0777, TRUE);
			}
		}
		else
		{
			copy($item->getPathname(), $target);
		}
	}
}

/**
 * Remove a directory tree.
 */
function ko8_remove(string $dir): void
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator AS $item)
	{
		$item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
	}

	rmdir($dir);
}

echo "ko8 · import legacy test suite\n\n";

if (is_dir($dest))
{
	ko8_remove($dest);
}

// suite => source tests directory
$suites = [
	'Core' => $root.'/system/tests',
];

foreach (glob($root.'/modules/*/tests', GLOB_ONLYDIR) AS $module_tests)
{
	$suites[ucfirst(basename(dirname($module_tests)))] = $module_tests;
}

$class_map = [];   // old class => [new class, file]
$skipped   = [];   // files dropped because they test removed functionality
$file_map  = [];   // old basename => new basename

foreach ($suites AS $suite => $source)
{
	// Modules resolve `tests/...` relative to their root, so the original
	// `tests/` directory has to be preserved inside the copied suite.
	$target = $dest.'/'.strtolower($suite).'/tests';
	ko8_copy($source, $target);

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator AS $file)
	{
		$path = $file->getPathname();

		if (substr($file->getFilename(), -4) !== '.php')
		{
			continue;
		}

		if (str_contains($path, 'test_data'))
		{
			continue;
		}

		$code = file_get_contents($path);

		// mcrypt was removed in PHP 7.2. Its test vectors describe an engine that
		// no longer exists, and the constants themselves are fatal in PHP 8.
		if (str_contains($code, 'MCRYPT_'))
		{
			unlink($path);
			$skipped[] = $path;
			continue;
		}

		if ( ! preg_match('/^\s*(?:abstract\s+|final\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $code, $m))
		{
			continue;
		}

		$old_class = $m[1];
		$old_base  = $file->getFilename();

		// Two files can share a basename (cache/CacheTest.php and
		// cache/request/client/CacheTest.php), so the subpath is part of the name.
		$relative  = substr($path, strlen($target) + 1, -4);
		$new_class = 'Legacy'.$suite.str_replace(['/', '-'], '_', $relative);

		// Helper base classes must not end in Test.php or PHPUnit complains.
		if (preg_match('/^\s*abstract\s+class/m', $code))
		{
			$new_class = preg_replace('/Test$/', 'Base', $new_class);
		}

		$new_base = $new_class.'.php';

		$class_map[$old_class] = [$new_class, $path];
		$file_map[$old_base]   = $new_base;
	}
}

$changed = 0;

foreach ($class_map AS $old_class => [$new_class, $path])
{
	$source = file_get_contents($path);
	$before = $source;

	// 1. Rename the declared class (only the first declaration in the file).
	$source = preg_replace(
		'/^(\s*(?:abstract\s+|final\s+)?class\s+)([A-Za-z_][A-Za-z0-9_]*)/m',
		'$1'.$new_class,
		$source,
		1
	);

	// 2. Point inheritance at the renamed legacy bases.
	foreach ($class_map AS $old => [$replacement, $file])
	{
		if ($old === $new_class)
		{
			continue;
		}

		$source = preg_replace(
			'/\bextends\s+'.preg_quote($old, '/').'\b/',
			'extends '.$replacement,
			$source
		);
	}

	// 2b. Self references and helper classes declared in the same file keep the
	//     old class name as a prefix (e.g. OldTest_TestableCookie). Rename them
	//     too, so the file is self-consistent.
	$source = preg_replace(
		'/\b'.preg_quote($old_class, '/').'(?![A-Za-z0-9])/',
		$new_class,
		$source
	);

	// 3. Unittest_TestCase is gone (both spellings are in the old sources).
	$source = preg_replace('/extends\s+(?:Kohana_)?Unittest_TestCase\b/', 'extends KO8_TestCase', $source);

	// 3b. A few files extend the framework's own `Kohana_TestCase`/`TestCase`
	//     alias. They need our helpers too.
	$source = preg_replace('/extends\s+TestCase\b/', 'extends KO8_TestCase', $source);

	// 4. Cross-file includes: find_file('tests/x', 'OldName') -> renamed file.
	foreach ($file_map AS $old_base => $new_base)
	{
		$source = str_replace(
			["'tests/", '"tests/'],
			["'tests/", '"tests/'],
			$source
		);

		$source = preg_replace(
			'/(find_file\(\s*[\'"]tests[^\'"]*[\'"]\s*,\s*[\'"])'.preg_quote(substr($old_base, 0, -4), '/').'([\'"])/',
			'$1'.substr($new_base, 0, -4).'$2',
			$source
		);
	}

	// 5. createMock() used PHPUnit 4's extended signature (and PHPUnit 11
	//    rejects configuring a method that does not exist). KO8_TestCase::mock()
	//    picks onlyMethods()/addMethods() per method with reflection.
	$source = preg_replace_callback(
		'/\$this->createMock\(\s*([^,()]+?)\s*(?:,\s*(\[[^\]]*\]))?\s*(?:,\s*(\[[^\]]*\]))?\s*(?:,\s*([^,()]+?))?\s*(?:,\s*(TRUE|FALSE))?\s*\)/',
		function (array $m): string
		{
			$target  = trim($m[1]);
			$methods = $m[2] ?? '[]';
			$args    = $m[3] ?? '[]';
			$name    = $m[4] ?? 'NULL';
			// PHPUnit 5.4's createMock() disabled the constructor; only an
			// explicit TRUE turned it back on.
			$ctor    = strtoupper(trim($m[5] ?? '')) === 'TRUE';

			$parts = [
				$target,
				$methods === '' ? '[]' : $methods,
				$ctor ? 'TRUE' : 'FALSE',
				$args === '' ? '[]' : $args,
				$name === '' ? 'NULL' : $name,
			];

			return '$this->mock('.implode(', ', $parts).')';
		},
		$source
	);

	// 4b. Every `->method('x')` in the file has to be part of the mock, or PHPUnit
	//     11 cannot configure it. Collect them and add them to each mock() call.
	if (preg_match_all('/->method\(\s*[\'"]([A-Za-z0-9_]+)[\'"]\s*\)/', $source, $configured))
	{
		$extra = array_values(array_unique($configured[1]));

		$source = preg_replace_callback(
			'/\$this->mock\(\s*([^,]+?)\s*,\s*(\[[^\]]*\])([^)]*)\)/',
			function (array $m) use ($extra): string
			{
				$existing = array_filter(array_map(
					fn ($value) => trim($value, " \t\n\r\0\x0B'\""),
					explode(',', trim($m[2], '[]'))
				));

				$merged = array_values(array_unique(array_merge($existing, $extra)));

				$list = implode(', ', array_map(fn ($value) => "'".$value."'", $merged));

				return '$this->mock('.$m[1].', ['.$list.']'.$m[3].')';
			},
			$source
		);
	}

	// 6. setUp()/tearDown() must declare void in PHPUnit 10+, including the
	//    unqualified `function setUp()` spelling used in a few old files.
	$source = preg_replace_callback(
		'/(?:(public|protected)\s+)?static\s+function\s+(setupBeforeClass|teardownAfterClass)\s*\(\s*\)\s*(?!:)/i',
		function (array $m): string
		{
			$proper = str_starts_with(strtolower($m[2]), 'setup') ? 'setUpBeforeClass' : 'tearDownAfterClass';

			return ($m[1] ?? 'public').' static function '.$proper.'(): void ';
		},
		$source
	);

	$source = preg_replace_callback(
		'/(?:(public|protected)\s+)?function\s+(setUp|tearDown)\s*\(\s*\)\s*(?!:)/',
		function (array $m): string
		{
			return ($m[1] ?? 'public').' function '.$m[2].'(): void ';
		},
		$source
	);

	$source = preg_replace_callback(
		'/(?:(public|protected)\s+)?static\s+function\s+(setUpBeforeClass|tearDownAfterClass)\s*\(\s*\)\s*(?!:)/i',
		function (array $m): string
		{
			return ($m[1] ?? 'public').' static function '.$m[2].'(): void ';
		},
		$source
	);

	// 6b. A stray `@test` on a provider turns it into a test with no arguments.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/)[\s\S])*?)\*\/(\s*)((?:public|protected)?\s*static\s+function\s+provider[A-Za-z0-9_]*\s*\()/',
		function (array $m): string
		{
			$doc = preg_replace('/^[ \t]*\*[ \t]*@test[ \t]*\r?\n/m', '', $m[1]);

			return $doc.'*/'.$m[2].$m[3];
		},
		$source
	);

	// 6c. A provider carrying `@test` would be executed as a test with no
	//     arguments. Strip the annotation from providers only.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/)[\s\S])*?)\*\/(\s*public\s+(?:static\s+)?function\s+(?:provider|provide)[A-Za-z0-9_]*\s*\()/',
		function (array $m): string
		{
			$doc = preg_replace('/^[ \t]*\*[ \t]*@test[ \t]*\r?\n/m', '', $m[1]);

			return $doc.'*/'.$m[2];
		},
		$source
	);

	// 7. Data providers must be static; annotations become attributes.
	$source = preg_replace_callback(
		'/(?:(public|protected|private)\s+)?function\s+((?:provider|provide)[A-Za-z0-9_]*)\s*\(/',
		function (array $m): string
		{
			return 'public static function '.$m[2].'(';
		},
		$source
	);

	// @dataProvider lives inside a docblock, so the attribute has to be lifted
	// out of it: PHPUnit 10+ only reads real attributes.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/).)*?)\*\/(\s*)((?:(?:public|protected|private)\s+)?(?:static\s+)?function\s+[A-Za-z0-9_]+\s*\()/s',
		function (array $m): string
		{
			$doc       = $m[1];
			$space     = $m[2];
			$signature = $m[3];

			if ( ! preg_match_all('/@dataProvider\s+([A-Za-z0-9_]+)/', $doc, $providers))
			{
				return $m[0];
			}

			$attributes = '';

			foreach ($providers[1] AS $provider)
			{
				$attributes .= "#[\\PHPUnit\\Framework\\Attributes\\DataProvider('".$provider."')]".$space;
			}

			// Remove the annotations from the docblock.
			$doc = preg_replace('/^[ \t]*\*[ \t]*@dataProvider\s+[A-Za-z0-9_]+[ \t]*\r?\n/m', '', $doc);

			return $doc.'*/'.$space.$attributes.$signature;
		},
		$source
	);

	// 7b. Data providers are static in PHPUnit 11, so they cannot use $this.
	//     Helpers that can be static are made static; everything else (mocks,
	//     assertions, instance helpers) goes through a constructor-less instance.
	$source = str_replace(
		['$this->dirSeparator(', '$this->hasInternet(', '$this->markAsRisky('],
		['self::dirSeparator(', 'self::hasInternet(', 'self::ko8_instance()->markAsRisky('],
		$source
	);

	$source = str_replace('(new self)->', 'self::ko8_instance()->', $source);

	// markAsRisky() was removed in PHPUnit 10; the test simply runs normally.
	$source = preg_replace('/^[ \t]*(?:self::ko8_instance\(\)|static|\$this)->markAsRisky\(\);?[ \t]*\r?\n/m', '', $source);

	$source = preg_replace_callback(
		'/public static function ((?:provider|provide)[A-Za-z0-9_]*)\([^)]*\)\s*\{(.*?)\n\t\}/s',
		function (array $m): string
		{
			$body = str_replace('$this->', 'self::ko8_instance()->', $m[2]);

			return 'public static function '.$m[1].'()'.'{'.$body."\n\t}";
		},
		$source
	);

	// 7c-bis. `@group` and `@depends` are also doc-comment metadata.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/)[\s\S])*?\*\/)(\s*(?:#\[[^\]]*\][\s\n]*)*\s*(?:(?:public|protected|private)\s+)?(?:static\s+)?function\s+[A-Za-z0-9_]+\s*\()/',
		function (array $m): string
		{
			$doc   = $m[1];
			$tail  = $m[2];
			$attrs = '';

			if (preg_match_all('/^[ \t]*\*[ \t]*@group[ \t]+([A-Za-z0-9_.-]+)/m', $doc, $groups))
			{
				foreach ($groups[1] AS $group)
				{
					$attrs .= "#[\\PHPUnit\\Framework\\Attributes\\Group('".$group."')]";
				}

				$doc = preg_replace('/^[ \t]*\*[ \t]*@group[ \t]+[A-Za-z0-9_.-]+[ \t]*\r?\n/m', '', $doc);
			}

			if (preg_match_all('/^[ \t]*\*[ \t]*@depends[ \t]+([A-Za-z0-9_]+)/m', $doc, $depends))
			{
				foreach ($depends[1] AS $dependency)
				{
					$attrs .= "#[\\PHPUnit\\Framework\\Attributes\\Depends('".$dependency."')]";
				}

				$doc = preg_replace('/^[ \t]*\*[ \t]*@depends[ \t]+[A-Za-z0-9_]+[ \t]*\r?\n/m', '', $doc);
			}

			return $attrs === '' ? $m[0] : $doc.$attrs.$tail;
		},
		$source
	);

	// 7d. `@test` in a docblock is metadata in a comment, which PHPUnit 12 will
	//     stop reading. Turn it into the attribute and drop the annotation.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/)[\s\S])*?)\*\/((?:\s*#\[[^\]]*\][\s\n]*)*\s*(?:(?:public|protected|private)\s+)?(?:static\s+)?function\s+[A-Za-z0-9_]+\s*\()/',
		function (array $m): string
		{
			if ( ! preg_match('/^[ \t]*\*[ \t]*@test[ \t]*\r?$/m', $m[1]))
			{
				return $m[0];
			}

			$doc = preg_replace('/^[ \t]*\*[ \t]*@test[ \t]*\r?\n/m', '', $m[1]);

			return $doc.'*/'."\n\t".'#[\PHPUnit\Framework\Attributes\Test]'.$m[2];
		},
		$source
	);

	// 7e. Class-level metadata (mostly @group) is also doc-comment metadata.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/)[\s\S])*?\*\/)(\s*(?:abstract\s+|final\s+)?class\s+[A-Za-z0-9_]+)/',
		function (array $m): string
		{
			$doc  = $m[1];
			$tail = $m[2];
			$attr = '';

			if (preg_match('/^[ \t]*\*[ \t]*@group\b/m', $doc))
			{
				$doc = preg_replace('/^[ \t]*\*[ \t]*@group[ \t]+[A-Za-z0-9_.-]+[ \t]*\r?\n/m', '', $doc);
			}

			return $doc.$tail;
		},
		$source
	);

	// 8. @covers -> attributes.
	$source = preg_replace_callback(
		'/@covers\s+([A-Za-z_\\\\][A-Za-z0-9_\\\\]*(?:::[A-Za-z0-9_]+)?)(?!\w)/',
		function (array $m): string
		{
			$target = $m[1];

			return str_contains($target, '::')
				? "#[\\PHPUnit\\Framework\\Attributes\\CoversMethod('".str_replace('::', "', '", $target)."')]"
				: "#[\\PHPUnit\\Framework\\Attributes\\CoversClass('".$target."')]";
		},
		$source
	);

	// 9. @expectedException -> expectException()/expectExceptionMessage().
	//    Any docblock may contain other tags between the two annotations, so the
	//    whole docblock is inspected instead of matching a fixed shape.
	$source = preg_replace_callback(
		'/(\/\*\*(?:(?!\*\/)[\s\S])*?)\*\/((?:(?!\{)[\s\S])*?\{)/',
		function (array $m): string
		{
			$doc  = $m[1];
			$tail = $m[2];

			if ( ! preg_match('/@expectedException\s+([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)/', $doc, $class))
			{
				return $m[0];
			}

			$prologue = "\$this->expectException('".trim($class[1])."');";

			if (preg_match('/@expectedExceptionMessage\s+([^\r\n*]+)/', $doc, $message))
			{
				$prologue .= "\n\t\t\$this->expectExceptionMessage("
					.var_export(trim($message[1]), TRUE).");";
			}

			// The annotations are dead weight now.
			$doc = preg_replace('/^[ \t]*\*[ \t]*@expectedException(Message)?\b[^\r\n]*\r?\n/m', '', $doc);

			return $doc.'*/'.$tail."\n\t\t".$prologue;
		},
		$source
	);

	// 9b. Locale names differ per OS (`en_US.utf8` does not exist on macOS).
	//     setlocale() accepts a list and uses the first one it finds.
	$source = preg_replace_callback(
		'/setlocale\(\s*(LC_[A-Z]+)\s*,\s*\'([a-z]{2}_[A-Z]{2})([^\']*)\'\s*\)/',
		function (array $m): string
		{
			$category = $m[1];
			$base     = $m[2];
			$suffix   = $m[3];

			$candidates = array_unique([
				$base.strtoupper($suffix),
				$base.$suffix,
				$base,
				$base.str_replace('utf8', 'UTF-8', $suffix),
			]);

			$list = implode(', ', array_map(fn ($value) => "'".$value."'", $candidates));

			return 'setlocale('.$category.', ['.$list.'])';
		},
		$source
	);

	// 9c. setMethods()/addMethods() were removed / reject real methods. The
	//     builder chain becomes a call to KO8_TestCase::mock().
	$source = preg_replace_callback(
		'/\$this->getMockBuilder\(\s*([\'\"][^\'\"]+[\'\"])\s*\)((?:\s*->(?:disableOriginalConstructor|setConstructorArgs|addMethods|setMethods|onlyMethods)\([^)]*\))+)\s*->(getMock|getMockForAbstractClass)\(\)/s',
		function (array $m): string
		{
			$class = $m[1];
			$chain = $m[2];

			$ctor = ! str_contains($chain, 'disableOriginalConstructor');
			$args = '[]';

			if (preg_match('/setConstructorArgs\(\s*(\[[^\]]*\])\s*\)/', $chain, $a))
			{
				$args = $a[1];
			}

			$methods = '[]';

			if (preg_match('/(?:addMethods|setMethods|onlyMethods)\(\s*(\[[^\]]*\])\s*\)/', $chain, $mm))
			{
				$methods = $mm[1];
			}

			return '$this->mock('.$class.', '.$methods.', '.($ctor ? 'TRUE' : 'FALSE').', '.$args.', NULL)';
		},
		$source
	);

	// 9d. ReflectionProperty::getValue() requires NULL (not the class name) for
	//     static properties since PHP 8.
	$source = preg_replace(
		'/->getValue\(\s*[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\s*\)/',
		'->getValue()',
		$source
	);

	// 10. assertRegExp() -> assertMatchesRegularExpression().
	$source = preg_replace('/\$this->assertRegExp\(/', '$this->assertMatchesRegularExpression(', $source);

	// 11. assertContains() only accepts arrays in PHPUnit 11; the old suite used
	//     it on strings too. Route both through a helper that dispatches.
	$source = preg_replace('/\$this->assertContains\(/', '$this->assertContainsCompat(', $source);
	$source = preg_replace('/\$this->assertNotContains\(/', '$this->assertNotContainsCompat(', $source);

	if ($source !== $before)
	{
		file_put_contents($path, $source);
		$changed++;
	}

	// 10b. Legacy tests declare properties on the fly (deprecated in PHP 8.2).
	//      Give each test class the ones it actually assigns.
	if (preg_match_all('/\$this->(_[A-Za-z][A-Za-z0-9_]*)\s*=/', $source, $props))
	{
		$declared = [];

		foreach (array_unique($props[1]) AS $property)
		{
			// The framework tests already declare some of them (with typos).
			$declared_pattern = '/\$(?:public|protected|private|static)\s+(?:\w+\s+)?'.preg_quote(ltrim($property, '_'), '/').'\b/';

			if ( ! preg_match($declared_pattern, $source))
			{
				$declared[] = "\tprotected \$$property;";
			}
		}

		if ($declared)
		{
			$source = preg_replace(
				'/^(class '.preg_quote($new_class, '/').'[^\n]*\n\{)/m',
				"$1\n".implode("\n", $declared),
				$source,
				1
			);
		}
	}

	// 11. Filename must match the class name.
	$new_path = dirname($path).'/'.$new_class.'.php';

	if ($new_path !== $path)
	{
		rename($path, $new_path);
	}
}

// 12. Known-obsolete assertions.
//
//     A few inherited tests assert things that stopped being true before PHP 8:
//     a module that is no longer shipped, byte offsets returned by strcmp(),
//     the path of the test file itself. They are rewritten here, not deleted, so
//     the intent survives and the change is reviewable.
$deviations = [
	'tests/legacy/core/tests/kohana/LegacyCorekohana_CoreTest.php' => [
		[
			"\t\t\$this->assertArrayHasKey('unittest', \$modules);",
			"\t\t// The unittest module is gone: ko8 ships a plain PHPUnit suite.\n"
			."\t\t// What matters here is that modules() returns the enabled modules.\n"
			."\t\t\$this->assertArrayHasKey('legacy-fixtures', \$modules);",
		],
	],
	'tests/legacy/core/tests/kohana/LegacyCorekohana_UTF8Test.php' => [
		[
			"\t\t\t['Cocoñùт',   'Cocoñ',   4],",
			"\t\t\t// strcmp() has returned -1/0/1 since PHP 8.2, not the byte delta.\n"
			."\t\t\t['Cocoñùт',   'Cocoñ',   1],",
		],
		[
			"\t\t\t['Cocoñùт',   'Coco',    6],",
			"\t\t\t['Cocoñùт',   'Coco',    1],",
		],
	],
	// The docblock tests use a class that does not exist ("MyClass") and expect a
	// link to it. Linking a class the API browser cannot render is a 404, so the
	// fixture now uses a real class: the test still checks tag formatting, which
	// is what it is for.
	'tests/legacy/userguide/tests/LegacyUserguideKodocTest.php' => [
		// The expected anchor is hardcoded as #method in the fixture.
		['MyClass::method()', 'Arr::method'],
		['MyClass', 'Arr'],
	],
	'tests/legacy/cache/tests/cache/request/client/LegacyCachecache_request_client_CacheTest.php' => [
		[
			"\t\t\t->with(\$this->stringEndsWith(\$key), \$this->identicalTo(0));",
			"\t\t\t->with(\$this->stringEndsWith(\$key), \$this->identicalTo(0))\n"
			."\t\t\t->willReturn(TRUE);",
		],
	],
	'tests/legacy/core/tests/kohana/LegacyCorekohana_ResponseTest.php' => [
		[
			"\t\t\$view = self::ko8_instance()->mock('View', ['__toString'], FALSE, [], NULL);\n"
			."\t\t\$view->expects(self::ko8_instance()->any())\n"
			."\t\t\t->method('__toString')\n"
			."\t\t\t->will(self::ko8_instance()->returnValue('foo'));",
			"\t\t// PHPUnit 11 cannot configure __toString() on a mock; a real\n"
			."\t\t// stringable object exercises the same code path in Response.\n"
			."\t\t\$view = self::stringable('foo');",
		],
	],
	'tests/legacy/core/tests/kohana/LegacyCorekohana_ExceptionTest.php' => [
		[
			"self::dirSeparator('Kohana_Exception [ 0 ]: foobar ~ SYSPATH/tests/kohana/ExceptionTest.php [ '.__LINE__.' ]')",
			"'Kohana_Exception [ 0 ]: foobar ~ '.Debug::path(__FILE__).' [ '.__LINE__.' ]'",
		],
	],
];

foreach ($deviations AS $file => $replacements)
{
	$path = $root.'/'.$file;

	if ( ! is_file($path))
	{
		continue;
	}

	$source = file_get_contents($path);

	foreach ($replacements AS [$search, $replace])
	{
		if (str_contains($source, $search))
		{
			$source = str_replace($search, $replace, $source);
		}
	}

	file_put_contents($path, $source);
}

echo "  suites:  ".implode(', ', array_keys($suites))."\n";
echo "  files:   ".count($class_map)."\n";
echo "  changed: $changed\n";

if ($skipped)
{
	echo "  skipped: ".count($skipped)." (mcrypt)\n";
}

echo "\n";

foreach ($class_map AS $old => [$new, $path])
{
	echo sprintf("  %-46s -> %s\n", $old, $new);
}
