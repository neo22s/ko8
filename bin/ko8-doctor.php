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
	 * Packages/classes whose signature change is a fatal error for subclasses.
	 *
	 * @var array<string,int>  class => parameters required by the parent method
	 */
	private const SIGNATURES = [
		'URL'   => 4,   // URL::site() and URL::base() gained $subdomain
		'Kohana_URL' => 4,
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
		$this->check_configuration($path);
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
			if ($this->next_meaningful($tokens, $i) !== '(')
			{
				continue;
			}

			$previous = $this->previous_meaningful($tokens, $i);

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

		for ($i = 0; $i + 3 < $count; $i++)
		{
			if ( ! is_array($tokens[$i]) OR $tokens[$i][0] !== T_VARIABLE OR $tokens[$i][1] !== '$this')
			{
				continue;
			}

			if ($this->next_meaningful($tokens, $i) !== T_OBJECT_OPERATOR AND $this->next_meaningful($tokens, $i) !== T_NULLSAFE_OBJECT_OPERATOR)
			{
				continue;
			}

			$name_token = $this->next_meaningful_token($tokens, $i);

			if ( ! is_array($name_token) OR $name_token[0] !== T_STRING)
			{
				continue;
			}

			// Only assignments, not reads or calls.
			if ($this->next_meaningful($tokens, $this->index_of($tokens, $name_token)) !== '=')
			{
				continue;
			}

			if (isset($declared[$name_token[1]]))
			{
				continue;
			}

			$this->add($inherits ? 'hint' : 'risk', $path, $tokens[$i][2], 'dynamic-property',
				'$this->'.$name_token[1].' is created on the fly; PHP 8.2 deprecates that'
				.($inherits ? ', unless a parent class declares it or defines __set().' : ' and ko8 treats it as an error in development.'),
				'Declare it: protected $'.$name_token[1].';');
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

			if ($this->next_meaningful($tokens, $i) !== T_DOUBLE_COLON)
			{
				continue;
			}

			$property = $this->next_meaningful_token($tokens, $i);

			if ( ! is_array($property) OR $property[1] !== '$salt')
			{
				continue;
			}

			$after = $this->index_of($tokens, $property);

			if ($this->next_meaningful($tokens, $after) !== '=')
			{
				continue;
			}

			$value = $this->next_meaningful_token($tokens, $after);

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

		$app = dirname($path, 2).'/config';

		if ( ! is_dir($app))
		{
			$app = dirname($path, 3).'/config';
		}

		if ( ! is_dir($app))
		{
			return;
		}

		if ( ! is_file($app.'/url.php'))
		{
			$this->add('risk', $path, 0, 'trusted-hosts',
				'No config/url.php: Koseven rejects unknown host names in URL::base(), so every URL throws on a new domain.',
				"// application/config/url.php\nreturn ['trusted_hosts' => ['example\\.com']];");
		}
	}

	/**
	 * Index of a token inside a token list, by identity.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function index_of(array $tokens, array $needle): int
	{
		foreach ($tokens AS $index => $token)
		{
			if ($token === $needle)
			{
				return $index;
			}
		}

		return -1;
	}

	/**
	 * The next token that is not whitespace or a comment.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 * @return array{0:int,1:string,2:int}|string|null
	 */
	private function next_meaningful_token(array $tokens, int $index): array|string|null
	{
		$count = count($tokens);

		for ($i = $index + 1; $i < $count; $i++)
		{
			$token = $tokens[$i];

			if (is_array($token) AND in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], TRUE))
			{
				continue;
			}

			return $token;
		}

		return NULL;
	}

	/**
	 * The value of the next meaningful token, which for punctuation is itself.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 */
	private function next_meaningful(array $tokens, int $index): array|string|null
	{
		return $this->next_meaningful_token($tokens, $index);
	}

	/**
	 * The previous token that is not whitespace or a comment.
	 *
	 * @param list<array{0:int,1:string,2:int}|string> $tokens
	 * @return array{0:int,1:string,2:int}|string|null
	 */
	private function previous_meaningful(array $tokens, int $index): array|string|null
	{
		for ($i = $index - 1; $i >= 0; $i--)
		{
			$token = $tokens[$i];

			if (is_array($token) AND in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], TRUE))
			{
				continue;
			}

			return $token;
		}

		return NULL;
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
