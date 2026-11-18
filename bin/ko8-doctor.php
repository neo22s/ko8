<?php
declare(strict_types=1);

/**
 * ko8 doctor: what would break if this application moved from Koseven to ko8.
 *
 * It reads the application, never executes it. Every finding names a file and a
 * line, says why it matters, and when there is a fix, shows it.
 *
 * The scan is token based, not regex based. That is the difference between a
 * useful report and noise: `$this->money_format()` calls the application's own
 * method, `function money_format(` declares it, and `$('.x').each(` inside a
 * string is JavaScript. A regex cannot tell those apart; the tokenizer can.
 *
 * Uso:
 *   php bin/ko8-doctor.php application
 *   php bin/ko8-doctor.php . --json
 *
 * Salida: 0 limpio, 1 hallazgos que romperan, 2 solo avisos.
 */
final class Ko8_Doctor {

	/**
	 * Functions that no longer exist, or are deprecated in PHP 8.
	 *
	 * @var array<string,array{0:string,1:string,null|string}>  name => [severity, message, fix]
	 */
	private const FUNCTIONS = [
		'each'            => ['break', 'each() was removed in PHP 8.0.', 'foreach'],
		'create_function' => ['break', 'create_function() was removed in PHP 8.0.', 'fn () => ... / function () use (...) {}'],
		'money_format'    => ['break', 'money_format() was removed in PHP 8.0.', 'NumberFormatter::formatCurrency()'],
		'ereg'            => ['break', 'ereg() was removed in PHP 7.0.', 'preg_match() with delimiters'],
		'eregi'           => ['break', 'eregi() was removed in PHP 7.0.', 'preg_match() with the i modifier'],
		'ereg_replace'    => ['break', 'ereg_replace() was removed in PHP 7.0.', 'preg_replace() with delimiters'],
		'eregi_replace'   => ['break', 'eregi_replace() was removed in PHP 7.0.', 'preg_replace() with the i modifier'],
		'split'           => ['risk',  'split() was removed in PHP 7.0.', 'explode() or preg_split()'],
		'utf8_encode'     => ['risk',  'utf8_encode() is deprecated in PHP 8.2.', "mb_convert_encoding(\$s, 'UTF-8', 'ISO-8859-1')"],
		'utf8_decode'     => ['risk',  'utf8_decode() is deprecated in PHP 8.2.', "mb_convert_encoding(\$s, 'ISO-8859-1', 'UTF-8')"],
		'strftime'        => ['risk',  'strftime() is deprecated in PHP 8.1.', 'IntlDateFormatter or DateTime::format()'],
		'gmstrftime'      => ['risk',  'gmstrftime() is deprecated in PHP 8.1.', 'IntlDateFormatter or DateTime::format()'],
		'mcrypt_encrypt'  => ['break', 'ext/mcrypt was removed in PHP 7.2.', "new Encrypt(['type' => 'openssl', 'cipher' => 'AES-128-CBC'])"],
		'mcrypt_decrypt'  => ['break', 'ext/mcrypt was removed in PHP 7.2.', "new Encrypt(['type' => 'openssl', 'cipher' => 'AES-128-CBC'])"],
		'mysql_query'     => ['break', 'ext/mysql was removed in PHP 7.0.', 'DB::query(Database::SELECT, $sql)->execute()'],
		'mysql_connect'   => ['break', 'ext/mysql was removed in PHP 7.0.', 'DB::query(Database::SELECT, $sql)->execute()'],
		'mysql_fetch_assoc' => ['break', 'ext/mysql was removed in PHP 7.0.', 'DB::query(...)->execute()->as_array()'],
		'mysql_real_escape_string' => ['break', 'ext/mysql was removed in PHP 7.0.', 'Use prepared statements'],
	];

	/**
	 * Methods an application may override, and how many parameters the parent
	 * has. An override with fewer is a fatal declaration error, and this is the
	 * most common real-world break when moving to Koseven: URL::site() and
	 * URL::base() gained $subdomain.
	 *
	 * @var array<string,array<string,int>>  parent class => method => arity
	 */
	private const SIGNATURES = [
		'URL'         => ['site' => 4, 'base' => 3],
		'Kohana_URL'  => ['site' => 4, 'base' => 3],
		'Request'     => ['factory' => 4],
		'Kohana_Request' => ['factory' => 4],
	];

	/**
	 * @var array<string,list<array{file:string,line:int,rule:string,message:string,fix:?string}>>
	 */
	private array $findings = [
		'break' => [],
		'risk'  => [],
		'hint'  => [],
	];

	private int $files = 0;

	public function __construct(private readonly string $root)
	{
	}

	/**
	 * Scan a directory tree, or a single file.
	 */
	public function scan(string $path): void
	{
		if (is_file($path))
		{
			$this->scan_file($path);

			return;
		}

		if ( ! is_dir($path))
		{
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator AS $file)
		{
			if ($file->getExtension() !== 'php')
			{
				continue;
			}

			// Vendored code and the framework itself are not the application's
			// responsibility.
			$normalized = str_replace('\\', '/', (string) $file->getPathname());

			if (str_contains($normalized, '/vendor/')
				OR str_contains($normalized, '/system/')
				OR str_contains($normalized, '/modules/')
				OR str_contains($normalized, '/node_modules/'))
			{
				continue;
			}

			$this->scan_file($file->getPathname());
		}
	}

	/**
	 * Check one file.
	 */
	private function scan_file(string $path): void
	{
		$code = file_get_contents($path);

		if ($code === FALSE OR $code === '')
		{
			return;
		}

		$this->files++;

		$tokens = token_get_all($code);

		$this->check_functions($path, $tokens);
		$this->check_dynamic_properties($path, $tokens);
		$this->check_cookie_salt($path, $tokens);
		$this->check_nullable_parameters($path, $tokens);
		$this->check_signatures($path, $code);
		$this->check_configuration($path);
	}

	/**
	 * Parameters typed without `?` but defaulting to NULL.
	 *
	 *     function f(array $data = NULL)     deprecated in PHP 8.4
	 *     function f(?array $data = NULL)    correct
	 *
	 * PHP still accepts the first form and treats it as nullable, so the code
	 * runs; what changed is that it now emits a deprecation on every call. This
	 * is the same fix Rector applied to 90 parameters in the framework.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function check_nullable_parameters(string $path, array $tokens): void
	{
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++)
		{
			if ( ! is_array($tokens[$i]) OR $tokens[$i][0] !== T_FUNCTION)
			{
				continue;
			}

			$open = $this->next_meaningful_index($tokens, $i);

			// Skip the function name: `function name(`.
			if ($open !== NULL AND is_array($tokens[$open]) AND $tokens[$open][0] === T_STRING)
			{
				$open = $this->next_meaningful_index($tokens, $open);
			}

			if ($open === NULL OR $tokens[$open] !== '(')
			{
				continue;
			}

			$close = $this->matching_paren($tokens, $open);

			if ($close === NULL)
			{
				continue;
			}

			foreach ($this->parameter_segments($tokens, $open, $close) AS [$start, $end])
			{
				$this->check_parameter($path, $tokens, $start, $end);
			}
		}
	}

	/**
	 * One parameter between two comma boundaries.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function check_parameter(string $path, array $tokens, int $start, int $end): void
	{
		$type = '';
		$name = '';
		$nullable = FALSE;
		$line = NULL;

		for ($i = $start; $i <= $end; $i++)
		{
			$token = $tokens[$i];

			if ($this->is_trivia($token))
			{
				continue;
			}

			if ($token === '?')
			{
				$nullable = TRUE;

				continue;
			}

			if (is_array($token) AND $token[0] === T_VARIABLE)
			{
				$name = $token[1];
				$line = $token[2];

				break;
			}

			// Anything before the variable and after the visibility modifier is
			// the type: `array`, `\Foo`, `int|string`, `self`.
			if (is_array($token) AND in_array($token[0], [
				T_STRING,
				T_ARRAY,
				T_CALLABLE,
				T_NAME_QUALIFIED,
				T_NAME_FULLY_QUALIFIED,
				T_NAME_RELATIVE,
			], TRUE))
			{
				$type .= $token[1];
			}
			elseif (in_array($token, ['|', '\\'], TRUE))
			{
				$type .= $token;
			}
		}

		if ($line === NULL OR $type === '' OR $nullable)
		{
			return;
		}

		// The default has to be NULL, and nothing else.
		$equals = NULL;

		for ($i = $start; $i <= $end; $i++)
		{
			if ($tokens[$i] === '=')
			{
				$equals = $i;
				break;
			}
		}

		if ($equals === NULL)
		{
			return;
		}

		$value = $this->next_meaningful_token($tokens, $equals);

		// `AND` binds looser than `=`: `$x = a() AND b()` assigns only a().
		// Use `&&` here, or the check silently reports every typed parameter.
		$default_null = is_array($value)
			&& $value[0] === T_STRING
			&& strcasecmp($value[1], 'NULL') === 0;

		if ( ! $default_null)
		{
			return;
		}

		$this->add('risk', $path, $line, 'implicit-nullable',
			'`'.$type.' '.$name.' = NULL` relies on an implicit nullable, deprecated in PHP 8.4.',
			'Write `?'.$type.' '.$name.' = NULL`.');
	}

	/**
	 * Index of the parenthesis that closes the one at $open.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function matching_paren(array $tokens, int $open): int|null
	{
		$depth = 0;
		$count = count($tokens);

		for ($i = $open; $i < $count; $i++)
		{
			$token = $tokens[$i];

			if ($token === '(')
			{
				$depth++;
			}
			elseif ($token === ')')
			{
				$depth--;

				if ($depth === 0)
				{
					return $i;
				}
			}
		}

		return NULL;
	}

	/**
	 * Split a parameter list into [start, end] index pairs.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 * @return list<array{0:int,1:int}>
	 */
	private function parameter_segments(array $tokens, int $open, int $close): array
	{
		$segments = [];
		$start = $open + 1;
		$depth = 0;

		// Stop before the closing parenthesis: it belongs to the list, not to
		// the last parameter. Counting it would push $depth to -1 and silently
		// drop that parameter.
		for ($i = $open + 1; $i < $close; $i++)
		{
			$token = $tokens[$i];

			if ($token === '(' OR $token === '[' OR $token === '{')
			{
				$depth++;
			}
			elseif ($token === ')' OR $token === ']' OR $token === '}')
			{
				$depth--;
			}

			if ($depth === 0 AND $token === ',')
			{
				if ($i > $start)
				{
					$segments[] = [$start, $i - 1];
				}

				$start = $i + 1;
			}
		}

		if ($close > $start)
		{
			$segments[] = [$start, $close - 1];
		}

		return $segments;
	}

	/**
	 * Calls to functions that no longer exist.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function check_functions(string $path, array $tokens): void
	{
		$count = count($tokens);

		for ($i = 0; $i < $count; $i++)
		{
			$token = $tokens[$i];

			if ( ! is_array($token) OR ($token[0] !== T_STRING AND $token[0] !== T_NAME_FULLY_QUALIFIED))
			{
				continue;
			}

			$name = strtolower(ltrim($token[1], '\\'));

			if ( ! isset(self::FUNCTIONS[$name]))
			{
				continue;
			}

			// It has to be a call: `name(`.
			if ($this->next_meaningful_token($tokens, $i) !== '(')
			{
				continue;
			}

			$previous = $this->previous_meaningful_token($tokens, $i);

			// `$this->money_format()` and `I18n::money_format()` are the
			// application's own methods, and `function money_format()` is a
			// declaration. Neither is the removed built-in.
			if (is_array($previous) AND in_array($previous[0], [
				T_OBJECT_OPERATOR,
				T_DOUBLE_COLON,
				T_FUNCTION,
				T_NEW,
				T_NULLSAFE_OBJECT_OPERATOR,
			], TRUE))
			{
				continue;
			}

			[$severity, $message, $fix] = self::FUNCTIONS[$name];

			$this->add($severity, $path, $token[2], 'function-'.$name, $message, $fix);
		}
	}

	/**
	 * Properties written to `$this` that the class never declares.
	 *
	 * PHP 8.2 deprecates those and ko8 turns deprecations into exceptions in
	 * development. A class with `__set` is exempt: assigning an undeclared
	 * property calls it instead.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function check_dynamic_properties(string $path, array $tokens): void
	{
		$code = '';

		foreach ($tokens AS $token)
		{
			$code .= is_array($token) ? $token[1] : $token;
		}

		// The attribute is the explicit opt-out.
		if (str_contains($code, 'AllowDynamicProperties'))
		{
			return;
		}

		if (preg_match('/function\s+__set\s*\(/', $code))
		{
			return;
		}

		$declared = [];

		if (preg_match_all('/(?:public|protected|private|var)\s+(?:static\s+)?(?:[?\\\\\w|]+\s+)?\$(\w+)/', $code, $matches))
		{
			$declared = array_flip($matches[1]);
		}

		// A subclass frequently stores its state on properties the parent
		// declares or intercepts. Without running the code that cannot be
		// proven, so it is a hint, not a break.
		$inherits = (bool) preg_match('/class\s+\w+\s+extends\s+\w+/', $code);

		$count = count($tokens);

		for ($i = 0; $i < $count; $i++)
		{
			$token = $tokens[$i];

			if ( ! is_array($token) OR $token[0] !== T_VARIABLE OR $token[1] !== '$this')
			{
				continue;
			}

			$arrow = $this->next_meaningful_index($tokens, $i);

			if ($arrow === NULL)
			{
				continue;
			}

			$arrow_token = $tokens[$arrow];

			if ( ! is_array($arrow_token) OR ($arrow_token[0] !== T_OBJECT_OPERATOR AND $arrow_token[0] !== T_NULLSAFE_OBJECT_OPERATOR))
			{
				continue;
			}

			$name_index = $this->next_meaningful_index($tokens, $arrow);

			if ($name_index === NULL)
			{
				continue;
			}

			$name_token = $tokens[$name_index];

			if ( ! is_array($name_token) OR $name_token[0] !== T_STRING)
			{
				continue;
			}

			// Only assignments, not reads or calls.
			$equals_index = $this->next_meaningful_index($tokens, $name_index);

			if ($equals_index === NULL OR $tokens[$equals_index] !== '=')
			{
				continue;
			}

			if (isset($declared[$name_token[1]]))
			{
				continue;
			}

			$this->add($inherits ? 'hint' : 'risk', $path, $token[2], 'dynamic-property',
				'$this->'.$name_token[1].' is created on the fly; PHP 8.2 deprecates that'
				.($inherits ? ', unless a parent class declares it or defines __set().' : ' and ko8 treats it as an error in development.'),
				'Declare it: protected $'.$name_token[1].';');
		}
	}

	/**
	 * Overrides of methods whose signature the framework changed.
	 */
	private function check_signatures(string $path, string $code): void
	{
		if ( ! preg_match('/class\s+\w+\s+extends\s+([\w\\\\]+)\s*(?:implements[^{]+)?{/', $code, $extends))
		{
			return;
		}

		$parent = $extends[1];

		if ( ! isset(self::SIGNATURES[$parent]) AND ! isset(self::SIGNATURES[str_replace('\\', '_', ltrim($parent, '\\'))]))
		{
			return;
		}

		$methods = self::SIGNATURES[$parent] ?? self::SIGNATURES[str_replace('\\', '_', ltrim($parent, '\\'))];

		foreach ($methods AS $method => $arity)
		{
			if ( ! preg_match('/function\s+'.$method.'\s*\(([^)]*)\)/', $code, $match))
			{
				continue;
			}

			$parameters = substr_count($match[1], '$');

			if ($parameters < $arity)
			{
				$this->add('break', $path, 0, 'signature-'.$method,
					$parent.'::'.$method.'() takes '.$arity.' parameters in Koseven, and this override takes '.$parameters.' — PHP 8 refuses to load the class.',
					'Add the missing parameter(s), even if unused. See '.$parent.'::'.$method.'() in the API browser.');
			}
		}
	}

	/**
	 * `Cookie::$salt` must not be NULL: every Cookie::set() throws without it.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function check_cookie_salt(string $path, array $tokens): void
	{
		$count = count($tokens);

		for ($i = 0; $i + 3 < $count; $i++)
		{
			if ( ! is_array($tokens[$i]) OR $tokens[$i][0] !== T_STRING OR strcasecmp($tokens[$i][1], 'Cookie') !== 0)
			{
				continue;
			}

			$colon = $this->next_meaningful_index($tokens, $i);

			if ($colon === NULL OR ! is_array($tokens[$colon]) OR $tokens[$colon][0] !== T_DOUBLE_COLON)
			{
				continue;
			}

			$property_index = $this->next_meaningful_index($tokens, $colon);

			if ($property_index === NULL OR ! is_array($tokens[$property_index]) OR $tokens[$property_index][1] !== '$salt')
			{
				continue;
			}

			$equals_index = $this->next_meaningful_index($tokens, $property_index);

			if ($equals_index === NULL OR $tokens[$equals_index] !== '=')
			{
				continue;
			}

			$value = $this->next_meaningful_token($tokens, $equals_index);

			$is_null = (is_array($value) AND $value[0] === T_STRING AND strcasecmp($value[1], 'NULL') === 0)
				OR $value === "''"
				OR $value === '""';

			if ($is_null)
			{
				$this->add('break', $path, $tokens[$i][2], 'cookie-salt',
					'Cookie::$salt is NULL, and every Cookie::set() throws without it.',
					"Cookie::\$salt = 'a-long-random-string';");
			}
		}
	}

	/**
	 * Configuration files that Koseven needs and a Kohana application does not
	 * have.
	 */
	private function check_configuration(string $path): void
	{
		// Only report once, from the bootstrap, so the message is actionable.
		if ( ! str_ends_with($path, 'bootstrap.php'))
		{
			return;
		}

		// bootstrap.php usually sits next to config/, but not always: openeshop
		// keeps it in oc/kohana/application/ and the config in oc/config/. Walk
		// up a few levels before deciding the file is missing.
		$dir = dirname($path);

		for ($i = 0; $i < 4 AND $dir !== '/' AND $dir !== ''; $i++)
		{
			if (is_file($dir.'/config/url.php'))
			{
				return;
			}

			$dir = dirname($dir);
		}

		$this->add('risk', $path, 0, 'trusted-hosts',
			'No config/url.php: Koseven rejects unknown host names in URL::base(), so every URL throws on a new domain.',
			"// application/config/url.php\nreturn ['trusted_hosts' => ['example\\.com']];");
	}

	/**
	 * Index of the next token that is not whitespace or a comment.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function next_meaningful_index(array $tokens, int $index): int|null
	{
		$count = count($tokens);

		for ($i = $index + 1; $i < $count; $i++)
		{
			if ($this->is_trivia($tokens[$i]))
			{
				continue;
			}

			return $i;
		}

		return NULL;
	}

	/**
	 * The next token that is not whitespace or a comment.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 * @return array{0:int,1:string,2:int}|string|null
	 */
	private function next_meaningful_token(array $tokens, int $index): array|string|null
	{
		$next = $this->next_meaningful_index($tokens, $index);

		return ($next === NULL) ? NULL : $tokens[$next];
	}

	/**
	 * The previous token that is not whitespace or a comment.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 * @return array{0:int,1:string,2:int}|string|null
	 */
	private function previous_meaningful_token(array $tokens, int $index): array|string|null
	{
		for ($i = $index - 1; $i >= 0; $i--)
		{
			if ($this->is_trivia($tokens[$i]))
			{
				continue;
			}

			return $tokens[$i];
		}

		return NULL;
	}

	/**
	 * Whitespace, comments and docblocks carry no syntax.
	 *
	 * @param array{0:int,1:string,2:int}|string $token
	 */
	private function is_trivia(array|string $token): bool
	{
		return is_array($token)
			AND ($token[0] === T_WHITESPACE OR $token[0] === T_COMMENT OR $token[0] === T_DOC_COMMENT);
	}

	/**
	 * Record a finding, ignoring duplicates.
	 */
	private function add(string $severity, string $file, int $line, string $rule, string $message, ?string $fix = NULL): void
	{
		foreach ($this->findings[$severity] AS $existing)
		{
			if ($existing['rule'] === $rule AND $existing['file'] === $this->relative($file) AND $existing['line'] === $line)
			{
				return;
			}
		}

		$this->findings[$severity][] = [
			'file'    => $this->relative($file),
			'line'    => $line,
			'rule'    => $rule,
			'message' => $message,
			'fix'     => $fix,
		];
	}

	/**
	 * Path relative to the scanned root, so the report reads like the project.
	 */
	private function relative(string $file): string
	{
		$prefix = $this->root.'/';

		return str_starts_with($file, $prefix) ? substr($file, strlen($prefix)) : $file;
	}

	/**
	 * Human readable report.
	 */
	public function report(): int
	{
		$total = count($this->findings['break']) + count($this->findings['risk']) + count($this->findings['hint']);

		printf("ko8 doctor · %d files scanned\n\n", $this->files);

		if ($total === 0)
		{
			echo "No findings. This application looks ready for ko8.\n";

			return 0;
		}

		$labels = [
			'break' => 'WILL BREAK',
			'risk'  => 'PROBABLY BREAKS',
			'hint'  => 'CHECK',
		];

		foreach ($labels AS $severity => $label)
		{
			if ( ! $this->findings[$severity])
			{
				continue;
			}

			usort($this->findings[$severity], static function (array $a, array $b): int {
				return [$a['file'], $a['line']] <=> [$b['file'], $b['line']];
			});

			printf("%s (%d)\n", $label, count($this->findings[$severity]));

			foreach ($this->findings[$severity] AS $finding)
			{
				printf("  %s:%d\n", $finding['file'], $finding['line']);
				printf("    %s\n", $finding['message']);

				if ($finding['fix'] !== NULL)
				{
					printf("    fix: %s\n", str_replace("\n", "\n         ", $finding['fix']));
				}
			}

			echo "\n";
		}

		printf("Summary: %d will break, %d probably break, %d to check.\n",
			count($this->findings['break']),
			count($this->findings['risk']),
			count($this->findings['hint']));

		echo "\nNext: docs/migration.md\n";

		return count($this->findings['break']) > 0 ? 1 : 2;
	}

	/**
	 * Machine readable report.
	 */
	public function json(): string
	{
		$summary = [];

		foreach ($this->findings AS $severity => $findings)
		{
			$summary[$severity] = count($findings);
		}

		return json_encode([
			'files_scanned' => $this->files,
			'summary'       => $summary,
			'findings'      => $this->findings,
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
	}
}

$path = $argv[1] ?? 'application';
$json = in_array('--json', $argv, TRUE);

$root = dirname(__DIR__);

$doctor = new Ko8_Doctor($root);
$doctor->scan(realpath($path) ?: $path);

if ($json)
{
	echo $doctor->json();
	exit(0);
}

exit($doctor->report());
