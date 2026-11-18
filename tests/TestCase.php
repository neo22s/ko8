<?php

/**
 * Base class for ko8 tests.
 *
 * Two jobs:
 *
 *  1. Replace `Unittest_TestCase`, the helper class the 2016 suite was written
 *     against: environment overrides, cache cleanup, path separators.
 *  2. Re-implement the assertions PHPUnit removed after 9.x
 *     (`assertTag`, `assertAttributeSame`, …) using reflection and DOM, so the
 *     inherited suite keeps testing the same things instead of being deleted.
 *
 * @package    Ko8
 * @category   Testing
 */
abstract class KO8_TestCase extends PHPUnit\Framework\TestCase
{
	/**
	 * Environment overrides applied in setUp(), restored in tearDown().
	 *
	 * Supported keys, checked in this order:
	 *  - `_GET`, `_POST`, `_SERVER`, `_COOKIE`, `_FILES`: superglobals
	 *  - `Class::$property`:             static properties
	 *  - `UPPERCASE_NAME` / HTTP_* keys: $_SERVER entries
	 *  - `group.key`:                    config values
	 *
	 * @var array
	 */
	protected $environmentDefault = [];

	/**
	 * Backups taken by setEnvironment(), keyed like the option itself.
	 */
	private array $environmentBackup = [];

	/**
	 * PHPUnit cannot configure `__toString()` on a mock, so a couple of inherited
	 * providers build a real View instead. This is the smallest object that
	 * behaves like the mock did.
	 */
	public static function stringable(string $value): object
	{
		return new class($value) {
			public function __construct(private string $value) {}

			public function __toString(): string
			{
				return $this->value;
			}
		};
	}

	/**
	 * The test case PHPUnit is currently running, if any.
	 */
	private static ?self $current = NULL;

	protected function setUp(): void
	{
		parent::setUp();

		self::$current = $this;

		if ($this->environmentDefault)
		{
			$this->setEnvironment($this->environmentDefault);
		}
	}

	protected function tearDown(): void
	{
		$this->restoreEnvironment();

		self::$current = NULL;

		parent::tearDown();
	}

	/**
	 * Override environment values for the duration of one test.
	 */
	public function setEnvironment(array $environment): void
	{
		foreach ($environment as $option => $value)
		{
			$backup = ! array_key_exists($option, $this->environmentBackup);

			// 1. Superglobals.
			if (in_array($option, ['_GET', '_POST', '_SERVER', '_COOKIE', '_FILES'], TRUE))
			{
				if ($backup)
				{
					$this->environmentBackup[$option] = $GLOBALS[$option] ?? NULL;
				}

				$GLOBALS[$option] = $value;

				continue;
			}

			// 2. Static properties, e.g. `Kohana::$base_url` or `Request::$initial`.
			if (str_contains($option, '::$'))
			{
				[$class, $property] = explode('::$', $option, 2);

				$reflection = new ReflectionClass($class);

				if ($backup)
				{
					$this->environmentBackup[$option] = $reflection->getStaticPropertyValue($property);
				}

				$reflection->setStaticPropertyValue($property, $value);

				continue;
			}

			// 3. Environment variables.
			if (preg_match('/^[A-Z_-]+$/', $option) OR isset($_SERVER[$option]))
			{
				if ($backup)
				{
					$this->environmentBackup[$option] = $_SERVER[$option] ?? '';
				}

				$_SERVER[$option] = $value;

				continue;
			}

			// 4. Config values, `group.key`.
			if ($backup)
			{
				$this->environmentBackup[$option] = Kohana::$config->load($option);
			}

			[$group, $path] = explode('.', $option, 2);

			Kohana::$config->load($group)->set($path, $value);
		}
	}

	/**
	 * Restore whatever setEnvironment() replaced, in reverse order.
	 */
	private function restoreEnvironment(): void
	{
		foreach (array_reverse($this->environmentBackup, TRUE) AS $option => $value)
		{
			if (in_array($option, ['_GET', '_POST', '_SERVER', '_COOKIE', '_FILES'], TRUE))
			{
				$GLOBALS[$option] = $value;

				continue;
			}

			if (str_contains($option, '::$'))
			{
				[$class, $property] = explode('::$', $option, 2);

				(new ReflectionClass($class))->setStaticPropertyValue($property, $value);

				continue;
			}

			if (preg_match('/^[A-Z_-]+$/', $option))
			{
				if ($value === '' )
				{
					unset($_SERVER[$option]);
				}
				else
				{
					$_SERVER[$option] = $value;
				}

				continue;
			}

			if (str_contains($option, '.'))
			{
				[$group, $path] = explode('.', $option, 2);

				Kohana::$config->load($group)->set($path, $value);
			}
		}

		$this->environmentBackup = [];
	}

	/**
	 * Replacement for PHPUnit's removed `at($index)`.
	 *
	 * PHPUnit 4 let you target the nth call to a method. KO8_AtInvocation
	 * restores exactly that behaviour.
	 */
	public function at(int $index): PHPUnit\Framework\MockObject\Rule\InvocationOrder
	{
		return new KO8_AtInvocation($index);
	}

	/**
	 * A test-case instance without running the constructor.
	 *
	 * Static data providers cannot use `$this`, but some of the inherited ones
	 * build mocks. They get a throwaway instance instead.
	 */
	public static function ko8_instance(): static
	{
		// While a test runs, mocks must be registered on *that* instance, or
		// PHPUnit refuses to configure them.
		if (self::$current instanceof static)
		{
			return self::$current;
		}

		static $instance = [];

		$class = static::class;

		if ( ! isset($instance[$class]))
		{
			$instance[$class] = (new ReflectionClass($class))->newInstance('ko8');
		}

		return $instance[$class];
	}

	/**
	 * Build a mock the way PHPUnit 4's `createMock($class, $methods, $args, $name, $ctor)`
	 * did.
	 *
	 * PHPUnit 11 needs to know, per method, whether it exists: `onlyMethods()` for
	 * real methods, `addMethods()` for the ones the old suite invented. A single
	 * `addMethods()` call fails on real methods, and vice versa, so the split has
	 * to happen here.
	 *
	 * @param array  $methods  methods to configure
	 * @param bool   $ctor     run the original constructor?
	 * @param array  $args     constructor arguments
	 * @param string $name     mock class name
	 */
	public function mock(string $class, array $methods = [], bool $ctor = FALSE, array $args = [], ?string $name = NULL): object
	{
		$builder = $this->getMockBuilder($class);

		if ( ! $ctor)
		{
			$builder->disableOriginalConstructor();
		}
		elseif ($args)
		{
			$builder->setConstructorArgs($args);
		}

		if ($name !== NULL AND $name !== '')
		{
			$builder->setMockClassName($name);
		}

		if ($methods)
		{
			$reflection = new ReflectionClass($class);

			$existing = [];
			$virtual  = [];

			foreach ($methods AS $method)
			{
				if ($reflection->hasMethod($method) AND ! $reflection->getMethod($method)->isFinal())
				{
					$existing[] = $method;
				}
				else
				{
					$virtual[] = $method;
				}
			}

			if ($existing)
			{
				$builder->onlyMethods($existing);
			}

			if ($virtual)
			{
				$builder->addMethods($virtual);
			}
		}
		// With no method list, PHPUnit mocks every method (what createMock() did).
		// Abstract classes and classes with abstract methods need the abstract
		// entry point, or MockObject refuses to generate the class.
		$reflection = new ReflectionClass($class);

		return ($reflection->isAbstract() OR $reflection->getMethods(ReflectionMethod::IS_ABSTRACT))
			? $builder->getMockForAbstractClass()
			: $builder->getMock();
	}

	/**
	 * Empty Kohana's file cache directory.
	 */
	public function cleanCacheDir(): void
	{
		$cache_dir = Kohana::$cache_dir;

		if ( ! is_dir($cache_dir))
		{
			return;
		}

		foreach (glob($cache_dir.'/*', GLOB_ONLYDIR) AS $dir)
		{
			foreach (glob($dir.'/*') AS $file)
			{
				unlink($file);
			}

			rmdir($dir);
		}
	}

	/**
	 * Path with the OS directory separator.
	 */
	public static function dirSeparator(string $path): string
	{
		return str_replace('/', DIRECTORY_SEPARATOR, $path);
	}

	/**
	 * Whether the machine running the tests has outbound connectivity.
	 */
	public static function hasInternet(): bool
	{
		$socket = @fsockopen('www.google.com', 80, $errno, $errstr, 1);

		if ( ! $socket)
		{
			return FALSE;
		}

		fclose($socket);

		return TRUE;
	}

	/**
	 * Read a (possibly protected or private) property of an object or class.
	 *
	 * Replaces PHPUnit's removed `readAttribute()`.
	 *
	 * @param string|object $class_or_object
	 */
	public static function readAttribute($class_or_object, string $attribute): mixed
	{
		$reflection = new ReflectionClass($class_or_object);
		$property = $reflection->getProperty($attribute);

		// setAccessible() became a no-op in PHP 8.1 but is still required on 8.0.
		if (PHP_VERSION_ID < 80100)
		{
			$property->setAccessible(TRUE);
		}

		return $property->getValue(
			is_object($class_or_object) ? $class_or_object : NULL
		);
	}

	/**
	 * Replaces PHPUnit's removed `assertAttributeSame()`.
	 *
	 * @param string|object $class_or_object
	 */
	public static function assertAttributeSame($expected, string $attribute, $class_or_object, string $message = ''): void
	{
		$actual = self::readAttribute($class_or_object, $attribute);

		static::assertSame($expected, $actual, $message ?: sprintf('Attribute %s does not match', $attribute));
	}

	/**
	 * Replaces PHPUnit's removed `assertAttributeEquals()`.
	 *
	 * @param string|object $class_or_object
	 */
	public static function assertAttributeEquals($expected, string $attribute, $class_or_object, string $message = ''): void
	{
		$actual = self::readAttribute($class_or_object, $attribute);

		static::assertEquals($expected, $actual, $message ?: sprintf('Attribute %s does not match', $attribute));
	}

	/**
	 * Replaces PHPUnit's removed `assertAttributeNotSame()`.
	 *
	 * @param string|object $class_or_object
	 */
	public static function assertAttributeNotSame($expected, string $attribute, $class_or_object, string $message = ''): void
	{
		$actual = self::readAttribute($class_or_object, $attribute);

		static::assertNotSame($expected, $actual, $message ?: sprintf('Attribute %s should differ', $attribute));
	}

	/**
	 * Replaces PHPUnit's removed `assertAttributeContains()`.
	 *
	 * @param string|object $class_or_object
	 */
	public static function assertAttributeContains($needle, string $attribute, $class_or_object, string $message = ''): void
	{
		$actual = self::readAttribute($class_or_object, $attribute);

		is_array($actual)
			? static::assertContains($needle, $actual, $message)
			: static::assertStringContainsString((string) $needle, (string) $actual, $message);
	}

	/**
	 * Replaces PHPUnit's removed `assertAttributeNotContains()`.
	 *
	 * @param string|object $class_or_object
	 */
	public static function assertAttributeNotContains($needle, string $attribute, $class_or_object, string $message = ''): void
	{
		$actual = self::readAttribute($class_or_object, $attribute);

		is_array($actual)
			? static::assertNotContains($needle, $actual, $message)
			: static::assertStringNotContainsString((string) $needle, (string) $actual, $message);
	}

	/**
	 * `assertContains()` that also accepts a string haystack.
	 *
	 * PHPUnit 11 requires an array here; the old suite used it on strings.
	 */
	public static function assertContainsCompat($needle, $haystack, string $message = ''): void
	{
		is_array($haystack) OR $haystack instanceof Traversable
			? static::assertContains($needle, $haystack, $message)
			: static::assertStringContainsString((string) $needle, (string) $haystack, $message);
	}

	/**
	 * `assertNotContains()` that also accepts a string haystack.
	 */
	public static function assertNotContainsCompat($needle, $haystack, string $message = ''): void
	{
		is_array($haystack) OR $haystack instanceof Traversable
			? static::assertNotContains($needle, $haystack, $message)
			: static::assertStringNotContainsString((string) $needle, (string) $haystack, $message);
	}

	/**
	 * Replaces PHPUnit's removed `assertInternalType()`.
	 */
	public static function assertInternalType(string $type, $actual, string $message = ''): void
	{
		$map = [
			'array'    => 'assertIsArray',
			'bool'     => 'assertIsBool',
			'boolean'  => 'assertIsBool',
			'float'    => 'assertIsFloat',
			'double'   => 'assertIsFloat',
			'int'      => 'assertIsInt',
			'integer'  => 'assertIsInt',
			'null'     => 'assertNull',
			'numeric'  => 'assertIsNumeric',
			'object'   => 'assertIsObject',
			'resource' => 'assertIsResource',
			'scalar'   => 'assertIsScalar',
			'string'   => 'assertIsString',
		];

		$method = $map[strtolower($type)] ?? NULL;

		if ($method === NULL)
		{
			static::fail('Unsupported internal type: '.$type);
		}

		static::$method($actual, $message);
	}

	/**
	 * Assert that a string contains a tag matching the given criteria.
	 *
	 * Replaces PHPUnit's removed `assertTag()`. The matcher is an array with
	 * `tag` and optionally `attributes`; both HTML and XML are accepted.
	 */
	public static function assertTag(array $matcher, string $actual, string $message = '', bool $is_html = TRUE): void
	{
		static::assertTrue(
			static::tag_match($matcher, $actual, $is_html),
			$message !== '' ? $message : 'Failed asserting that the document contains the expected tag.'
		);
	}

	/**
	 * Inverse of assertTag().
	 */
	public static function assertNotTag(array $matcher, string $actual, string $message = '', bool $is_html = TRUE): void
	{
		static::assertFalse(
			static::tag_match($matcher, $actual, $is_html),
			$message !== '' ? $message : 'Failed asserting that the document does not contain the tag.'
		);
	}

	/**
	 * Does the document contain a tag matching the matcher?
	 *
	 * Supports the subset of PHPUnit 4's matcher that the inherited suite uses:
	 * `tag`, `attributes`, `content`, `id`, `parent`, `ancestor`, `descendant`
	 * and `child`.
	 */
	protected static function tag_match(array $matcher, string $actual, bool $is_html = TRUE): bool
	{
		$document = new DOMDocument;

		$previous = libxml_use_internal_errors(TRUE);
		$loaded   = $is_html
			? $document->loadHTML($actual, LIBXML_NOWARNING | LIBXML_NOERROR)
			: $document->loadXML($actual);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ( ! $loaded)
		{
			return FALSE;
		}

		$tag = $matcher['tag'] ?? NULL;

		if ($tag === NULL)
		{
			return FALSE;
		}

		foreach ($document->getElementsByTagName($tag) AS $element)
		{
			if (static::tag_matches_element($matcher, $element))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Does one element satisfy a matcher?
	 */
	private static function tag_matches_element(array $matcher, DOMElement $element): bool
	{
		// tag
		if (isset($matcher['tag']) AND strcasecmp($element->tagName, $matcher['tag']) !== 0)
		{
			return FALSE;
		}

		// id
		if (isset($matcher['id']) AND $element->getAttribute('id') !== (string) $matcher['id'])
		{
			return FALSE;
		}

		// attributes
		foreach (($matcher['attributes'] ?? []) AS $name => $expected)
		{
			if ($expected === TRUE OR $expected === '')
			{
				if ( ! $element->hasAttribute($name))
				{
					return FALSE;
				}

				continue;
			}

			if ($element->getAttribute($name) !== (string) $expected)
			{
				return FALSE;
			}
		}

		// content
		if (isset($matcher['content']) AND trim($element->textContent) !== trim((string) $matcher['content']))
		{
			return FALSE;
		}

		// parent
		if (isset($matcher['parent']))
		{
			$parent = $element->parentNode;

			if ( ! $parent instanceof DOMElement OR ! static::tag_matches_element($matcher['parent'], $parent))
			{
				return FALSE;
			}
		}

		// ancestor
		if (isset($matcher['ancestor']))
		{
			$found = FALSE;

			for ($node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode)
			{
				if (static::tag_matches_element($matcher['ancestor'], $node))
				{
					$found = TRUE;
					break;
				}
			}

			if ( ! $found)
			{
				return FALSE;
			}
		}

		// descendant
		if (isset($matcher['descendant']))
		{
			$found = FALSE;

			foreach ($element->getElementsByTagName('*') AS $node)
			{
				if (static::tag_matches_element($matcher['descendant'], $node))
				{
					$found = TRUE;
					break;
				}
			}

			if ( ! $found)
			{
				return FALSE;
			}
		}

		// child
		if (isset($matcher['child']))
		{
			$found = FALSE;

			foreach ($element->childNodes AS $node)
			{
				if ($node instanceof DOMElement AND static::tag_matches_element($matcher['child'], $node))
				{
					$found = TRUE;
					break;
				}
			}

			if ( ! $found)
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}
