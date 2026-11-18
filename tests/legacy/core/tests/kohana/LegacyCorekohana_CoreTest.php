<?php

/**
 * Tests Kohana Core
 *
 * @TODO Use a virtual filesystem (see phpunit doc on mocking fs) for find_file etc.
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_CoreTest extends KO8_TestCase
{
	protected $old_modules = [];

	/**
	 * Captures the module list as it was before this test
	 *
	 * @return null
	 */
	// @codingStandardsIgnoreStart
	public function setUp(): void // @codingStandardsIgnoreEnd
	{
		parent::setUp();
		$this->old_modules = Kohana::modules();
	}

	/**
	 * Restores the module list
	 *
	 * @return null
	 */
	// @codingStandardsIgnoreStart
	public function tearDown(): void // @codingStandardsIgnoreEnd
	{
		Kohana::modules($this->old_modules);
	}

	/**
	 * Provides test data for test_sanitize()
	 *
	 * @return array
	 */
	public static function provider_sanitize(){
		return [
			// $value, $result
			['foo', 'foo'],
			["foo\r\nbar", "foo\nbar"],
			["foo\rbar", "foo\nbar"],
		];
	}

	/**
	 * Tests Kohana::santize()
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'sanitize')]
	 * @param boolean $value  Input for Kohana::sanitize
	 * @param boolean $result Output for Kohana::sanitize
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_sanitize')]
	public function test_sanitize($value, $result)
	{
		$this->assertSame($result, Kohana::sanitize($value));
	}

	/**
	 * Passing FALSE for the file extension should prevent appending any extension.
	 * See issue #3214
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'find_file')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_find_file_no_extension()
	{
		// EXT is manually appened to the _file name_, not passed as the extension
		$path = Kohana::find_file('classes', $file = 'Kohana/Core'.EXT, FALSE);

		$this->assertInternalType('string', $path);

		$this->assertStringEndsWith($file, $path);
	}

	/**
	 * If a file can't be found then find_file() should return FALSE if
	 * only a single file was requested, or an empty array if multiple files
	 * (i.e. configuration files) were requested
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'find_file')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_find_file_returns_false_or_array_on_failure()
	{
		$this->assertFalse(Kohana::find_file('configy', 'zebra'));

		$this->assertSame([], Kohana::find_file('configy', 'zebra', NULL, TRUE));
	}

	/**
	 * Kohana::list_files() should return an array on success and an empty array on failure
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'list_files')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_list_files_returns_array_on_success_and_failure()
	{
		$files = Kohana::list_files('config');

		$this->assertInternalType('array', $files);
		$this->assertGreaterThan(3, count($files));

		$this->assertSame([], Kohana::list_files('geshmuck'));
	}

	/**
	 * Provides test data for testCache()
	 *
	 * @return array
	 */
	public static function provider_cache(){
		return [
			// $value, $result
			['foo', 'hello, world', 10],
			['bar', NULL, 10],
			['bar', NULL, -10],
		];
	}

	/**
	 * Tests Kohana::cache()
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'cache')]
	 * @param boolean $key      Key to cache/get for Kohana::cache
	 * @param boolean $value    Output from Kohana::cache
	 * @param boolean $lifetime Lifetime for Kohana::cache
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_cache')]
	public function test_cache($key, $value, $lifetime)
	{
		Kohana::cache($key, $value, $lifetime);
		$this->assertEquals($value, Kohana::cache($key));
	}

	/**
	 * Tests Kohana::find_file() cache is saved on shutdown.
	 * 
	 * @test
	 */
	/*public function test_find_file_cache_saved()
	{
		$old_caching     = Kohana::$caching;
		$old_errors      = Kohana::$errors;
		Kohana::$caching = TRUE;
		Kohana::$errors  = FALSE;

		// trigger find_file() so Kohana::$_files_changed is set to TRUE
		Kohana::find_file('abc', 'def');

		// trigger shutdown so kohana write to file cache
		Kohana::shutdown_handler();

		$this->assertInternalType('array', Kohana::file_cache('Kohana::find_file()'));	    

		Kohana::$caching = $old_caching;
		Kohana::$errors  = $old_errors;
	}*/

	/**
	 * Provides test data for test_message()
	 *
	 * @return array
	 */
	public static function provider_message(){
		return [
			['no_message_file', 'anything', 'default', 'default'],
			['no_message_file', NULL, 'anything', []],
			['kohana_core_message_tests', 'bottom_only', 'anything', 'inherited bottom message'],
			['kohana_core_message_tests', 'cfs_replaced', 'anything', 'overriding cfs_replaced message'],
			['kohana_core_message_tests', 'top_only', 'anything', 'top only message'],
			['kohana_core_message_tests', 'missing', 'default', 'default'],
			['kohana_core_message_tests', NULL, 'anything',
				[
					'bottom_only'  => 'inherited bottom message',
					'cfs_replaced' => 'overriding cfs_replaced message',
					'top_only'     => 'top only message'
				]
			],
		];
	}

	/**
	 * Tests Kohana::message()
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'message')]
	 * @param string $file     to pass to Kohana::message
	 * @param string $key      to pass to Kohana::message
	 * @param string $default  to pass to Kohana::message
	 * @param string $expected Output for Kohana::message
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_message')]
	public function test_message($file, $key, $default, $expected)
	{
		$test_path = realpath(dirname(__FILE__).'/../test_data/message_tests');
		Kohana::modules(['top' => "$test_path/top_module", 'bottom' => "$test_path/bottom_module"]);

		$this->assertEquals($expected, Kohana::message($file, $key, $default, $expected));
	}

	/**
	 * Provides test data for test_error_handler()
	 *
	 * @return array
	 */
	public static function provider_error_handler(){
		return [
			[1, 'Foobar', 'foobar.php', __LINE__],
		];
	}

	/**
	 * Tests Kohana::error_handler()
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'error_handler')]
	 * @param boolean $code  Input for Kohana::sanitize
	 * @param boolean $error  Input for Kohana::sanitize
	 * @param boolean $file  Input for Kohana::sanitize
	 * @param boolean $line Output for Kohana::sanitize
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_error_handler')]
	public function test_error_handler($code, $error, $file, $line)
	{
		$error_level = error_reporting();
		error_reporting(E_ALL);
		try
		{
			Kohana::error_handler($code, $error, $file, $line);
		}
		catch (Exception $e)
		{
			$this->assertEquals($code, $e->getCode());
			$this->assertEquals($error, $e->getMessage());
		}
		error_reporting($error_level);
	}

	/**
	 * Provides test data for test_modules_sets_and_returns_valid_modules()
	 *
	 * @return array
	 */
	public static function provider_modules_detects_invalid_modules(){
		return [
			[['unittest' => MODPATH.'fo0bar']],
			[['unittest' => MODPATH.'unittest', 'fo0bar' => MODPATH.'fo0bar']],
		];
	}

	/**
	 * Tests Kohana::modules()
	 *
	 * @param boolean $source   Input for Kohana::modules
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_modules_detects_invalid_modules')]
	public function test_modules_detects_invalid_modules($source)
	{
		$this->expectException('Kohana_Exception');
		$modules = Kohana::modules();

		try
		{
			Kohana::modules($source);
		}
		catch(Exception $e)
		{
			// Restore modules
			Kohana::modules($modules);

			throw $e;
		}

		// Restore modules
		Kohana::modules($modules);
	}

	/**
	 * Provides test data for test_modules_sets_and_returns_valid_modules()
	 *
	 * @return array
	 */
	public static function provider_modules_sets_and_returns_valid_modules(){
		return [
			[[], []],
			[['module' => __DIR__], ['module' => self::dirSeparator(__DIR__.'/')]],
		];
	}

	/**
	 * Tests Kohana::modules()
	 *
	 * @param boolean $source   Input for Kohana::modules
	 * @param boolean $expected Output for Kohana::modules
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_modules_sets_and_returns_valid_modules')]
	public function test_modules_sets_and_returns_valid_modules($source, $expected)
	{
		$modules = Kohana::modules();

		try
		{
			$this->assertEquals($expected, Kohana::modules($source));
		}
		catch(Exception $e)
		{
			Kohana::modules($modules);

			throw $e;
		}

		Kohana::modules($modules);
	}

	/**
	 * To make the tests as portable as possible this just tests that
	 * you get an array of modules when you can Kohana::modules() and that
	 * said array contains unittest
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'modules')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_modules_returns_array_of_modules()
	{
		$modules = Kohana::modules();

		$this->assertInternalType('array', $modules);

		// The unittest module is gone: ko8 ships a plain PHPUnit suite.
		// What matters here is that modules() returns the enabled modules.
		$this->assertArrayHasKey('legacy-fixtures', $modules);
	}

	/**
	 * Tests Kohana::include_paths()
	 *
	 * The include paths must contain the apppath and syspath
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana', 'include_paths')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_include_paths()
	{
		$include_paths = Kohana::include_paths();
		$modules       = Kohana::modules();

		$this->assertInternalType('array', $include_paths);

		// We must have at least 2 items in include paths (APP / SYS)
		$this->assertGreaterThan(2, count($include_paths));
		// Make sure said paths are in the include paths
		// And make sure they're in the correct positions
		$this->assertSame(APPPATH, reset($include_paths));
		$this->assertSame(SYSPATH, end($include_paths));

		foreach ($modules as $module)
		{
			$this->assertContainsCompat($module, $include_paths);
		}
	}
}

