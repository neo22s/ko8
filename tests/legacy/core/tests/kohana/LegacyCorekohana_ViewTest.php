<?php

/**
 * Tests the View class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_ViewTest extends KO8_TestCase
{
	protected static $old_modules = [];

	/**
	 * Setups the filesystem for test view files
	 *
	 * @return null
	 */
	// @codingStandardsIgnoreStart
	public static function setUpBeforeClass(): void // @codingStandardsIgnoreEnd
	{
		self::$old_modules = Kohana::modules();

		$new_modules = self::$old_modules+[
			'test_views' => realpath(dirname(__FILE__).'/../test_data/')
		];
		Kohana::modules($new_modules);
	}

	/**
	 * Restores the module list
	 *
	 * @return null
	 */
	// @codingStandardsIgnoreStart
	public static function tearDownAfterClass(): void // @codingStandardsIgnoreEnd
	{
		Kohana::modules(self::$old_modules);
	}

	/**
	 * Provider for test_instaniate
	 *
	 * @return array
	 */
	public static function provider_instantiate(){
		return [
			['kohana/error', FALSE],
			['test.css', FALSE],
			['doesnt_exist', TRUE],
		];
	}

	/**
	 * Provider to test_set
	 *
	 * @return array
	 */
	public static function provider_set(){
		return [
			['foo', 'bar', 'foo', 'bar'],
			[['foo' => 'bar'], NULL, 'foo', 'bar'],
			[new ArrayIterator(['foo' => 'bar']), NULL, 'foo', 'bar'],
		];
	}

	/**
	 * Tests that we can instantiate a view file
	 *
	 *
	 * @return null
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_instantiate')]
	public function test_instantiate($path, $expects_exception)
	{
		try
		{
			$view = new View($path);
			$this->assertSame(FALSE, $expects_exception);
		}
		catch(View_Exception $e)
		{
			$this->assertSame(TRUE, $expects_exception);
		}
	}

	/**
	 * Tests that we can set using string, array or Traversable object
	 *
	 *
	 * @return null
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_set')]
	public function test_set($data_key, $value, $test_key, $expected)
	{
		$view = View::factory()->set($data_key, $value);
		$this->assertSame($expected, $view->$test_key);
	}

	/**
	 * Tests that we can set global using string, array or Traversable object
	 *
	 *
	 * @return null
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_set')]
	public function test_set_global($data_key, $value, $test_key, $expected)
	{
		$view = View::factory();
		$view::set_global($data_key, $value);
		$this->assertSame($expected, $view->$test_key);
	}
}
