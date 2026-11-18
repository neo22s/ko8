<?php

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * The doctor is a script, so it is tested the way it is used: as a process.
 *
 * Its whole value is the ratio of real findings to noise, and that ratio is
 * decided by the tokenizer. These tests pin down the cases that a regex would
 * get wrong: a method that shares a name with a removed function, a call inside
 * a string, a declaration, and a property that is declared or intercepted.
 */
#[CoversNothing]
final class DoctorTest extends KO8_TestCase
{
	private string $fixture;

	protected function setUp(): void
	{
		$this->fixture = sys_get_temp_dir().'/ko8-doctor-'.bin2hex(random_bytes(6));

		mkdir($this->fixture, 0777, TRUE);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->fixture.'/{,*/}*', GLOB_BRACE) ?: [] AS $file)
		{
			is_file($file) ? unlink($file) : NULL;
		}

		@rmdir($this->fixture);
	}

	/**
	 * Run the doctor and return its decoded report.
	 *
	 * @return array<string,mixed>
	 */
	private function run_doctor(string $code): array
	{
		file_put_contents($this->fixture.'/Subject.php', $code);

		$command = escapeshellarg(PHP_BINARY).' '
			.escapeshellarg(dirname(__DIR__, 2).'/bin/ko8-doctor.php').' '
			.escapeshellarg($this->fixture).' --json 2>&1';

		$output = shell_exec($command);

		$this->assertIsString($output, 'the doctor produced no output');

		$report = json_decode($output, TRUE);

		$this->assertIsArray($report, "the doctor did not produce JSON:\n$output");

		return $report;
	}

	/**
	 * @param array<string,mixed> $report
	 * @return list<string>
	 */
	private function rules(array $report, string $severity): array
	{
		return array_column($report['findings'][$severity], 'rule');
	}

	public function test_a_removed_function_is_reported(): void
	{
		$report = $this->run_doctor('<?php $rows = each($data);');

		$this->assertContains('function-each', $this->rules($report, 'break'));
	}

	/**
	 * `I18n::money_format()` is an application method, and it was reported as
	 * the removed built-in by every regex version of this scan.
	 */
	public function test_a_method_named_like_a_removed_function_is_not_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class I18n
{
	public static function money_format($number)
	{
		return $this->money_format($number);
	}
}
PHP);

		$this->assertSame([], $this->rules($report, 'break'));
	}

	public function test_a_declaration_is_not_a_call(): void
	{
		$report = $this->run_doctor('<?php function money_format($format, $number) {}');

		$this->assertSame([], $this->rules($report, 'break'));
	}

	/**
	 * A template with jQuery in it is not PHP calling each().
	 */
	public function test_a_call_inside_a_string_is_not_a_call(): void
	{
		$report = $this->run_doctor('<?php $js = "$(\'.x\').each(function () {});";');

		$this->assertSame([], $this->rules($report, 'break'));
	}

	public function test_an_undeclared_property_is_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class Widget
{
	public function __construct()
	{
		$this->title = 'x';
	}
}
PHP);

		$this->assertContains('dynamic-property', $this->rules($report, 'risk'));
	}

	public function test_a_declared_property_is_not_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class Widget
{
	protected $title;

	public function __construct()
	{
		$this->title = 'x';
	}
}
PHP);

		$this->assertNotContains('dynamic-property', $this->rules($report, 'risk'));
	}

	/**
	 * A class with __set() stores the value instead of creating a property.
	 */
	public function test_a_class_with_a_setter_is_not_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class Widget
{
	public function __set($name, $value) {}

	public function __construct()
	{
		$this->title = 'x';
	}
}
PHP);

		$this->assertNotContains('dynamic-property', $this->rules($report, 'risk'));
	}

	/**
	 * URL::site() gained $subdomain in Koseven. An override without it is a
	 * fatal declaration error, which is the most common real migration break.
	 */
	public function test_an_incompatible_override_is_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class URL extends Kohana_URL
{
	public static function site($uri = '', $protocol = NULL, $index = TRUE)
	{
		return parent::site($uri, $protocol, $index);
	}
}
PHP);

		$this->assertContains('signature-site', $this->rules($report, 'break'));
	}

	public function test_a_compatible_override_is_not_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class URL extends Kohana_URL
{
	public static function site($uri = '', $protocol = NULL, $index = TRUE, $subdomain = NULL)
	{
		return parent::site($uri, $protocol, $index, $subdomain);
	}
}
PHP);

		$this->assertSame([], $this->rules($report, 'break'));
	}

	/**
	 * PHP 8.4 deprecates `array $x = NULL`; `?array $x = NULL` is the fix. The
	 * last parameter is the one a naive splitter drops, so it is the one to test.
	 */
	public function test_an_implicit_nullable_on_the_last_parameter_is_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class Service
{
	public function find(Model_Order $order, array $options = NULL) {}
}
PHP);

		$this->assertContains('implicit-nullable', $this->rules($report, 'risk'));
	}

	public function test_an_explicit_nullable_is_not_reported(): void
	{
		$report = $this->run_doctor(<<<'PHP'
<?php
class Service
{
	public function find(Model_Order $order, ?array $options = NULL) {}
}
PHP);

		$this->assertNotContains('implicit-nullable', $this->rules($report, 'risk'));
	}

	public function test_a_null_cookie_salt_is_reported(): void
	{
		$report = $this->run_doctor('<?php Cookie::$salt = NULL;');

		$this->assertContains('cookie-salt', $this->rules($report, 'break'));
	}

	public function test_a_configured_cookie_salt_is_not_reported(): void
	{
		$report = $this->run_doctor("<?php Cookie::\$salt = 'a-long-random-string';");

		$this->assertNotContains('cookie-salt', $this->rules($report, 'break'));
	}

	/**
	 * Exit codes are the interface for a CI job.
	 */
	public function test_exit_code_reflects_the_worst_finding(): void
	{
		$script = escapeshellarg(PHP_BINARY).' '
			.escapeshellarg(dirname(__DIR__, 2).'/bin/ko8-doctor.php').' '
			.escapeshellarg($this->fixture);

		file_put_contents($this->fixture.'/Clean.php', '<?php echo "hello";');
		exec($script.' 2>&1', $output, $clean);
		$this->assertSame(0, $clean, 'a clean file should exit 0');

		file_put_contents($this->fixture.'/Broken.php', '<?php $x = each($y);');
		exec($script.' 2>&1', $output, $broken);
		$this->assertSame(1, $broken, 'a break should exit 1');
	}
}
