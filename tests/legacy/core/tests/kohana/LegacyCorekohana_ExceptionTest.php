<?php

/**
 * Tests Kohana Exception Class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_ExceptionTest extends KO8_TestCase
{
	/**
	 * Provides test data for test_constructor()
	 *
	 * @return array
	 */
	public static function provider_constructor(){
		return [
			[[''], '', 0],
			[[':a'], ':a', 0],

			[[':a', NULL], ':a', 0],
			[[':a', []], ':a', 0],
			[[':a', [':a' => 'b']], 'b', 0],
			[[':a :b', [':a' => 'c', ':b' => 'd']], 'c d', 0],

			[[':a', NULL, 5], ':a', 5],
			// #3358
			[[':a', NULL, '3F000'], ':a', '3F000'],
			// #3404
			[[':a', NULL, '42S22'], ':a', '42S22'],
			// #3927
			[[':a', NULL, 'b'], ':a', 'b'],
			// #4039
			[[':a', NULL, '25P01'], ':a', '25P01'],
		];
	}

	/**
	 * Tests Kohana_Kohana_Exception::__construct()
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana_Kohana_Exception', '__construct')]
	 * @param array             $arguments          Arguments
	 * @param string            $expected_message   Value from getMessage()
	 * @param integer|string    $expected_code      Value from getCode()
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_constructor')]
	public function test_constructor($arguments, $expected_message, $expected_code)
	{
		switch (count($arguments))
		{
			case 1:
				$exception = new Kohana_Exception(reset($arguments));
			break;
			case 2:
				$exception = new Kohana_Exception(reset($arguments), next($arguments));
			break;
			default:
				$exception = new Kohana_Exception(reset($arguments), next($arguments), next($arguments));
		}

		$this->assertSame($expected_code, $exception->getCode());
		$this->assertSame($expected_message, $exception->getMessage());
	}

	/**
	 * Provides test data for test_text()
	 *
	 * @return array
	 */
	public static function provider_text(){
		return [
			[new Kohana_Exception('foobar'), 'Kohana_Exception [ 0 ]: foobar ~ '.Debug::path(__FILE__).' [ '.__LINE__.' ]'],
		];
	}

	/**
	 * Tests Kohana_Exception::text()
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana_Exception', 'text')]
	 * @param object $exception exception to test
	 * @param string $expected  expected output
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_text')]
	public function test_text($exception, $expected)
	{
		$this->assertEquals($expected, Kohana_Exception::text($exception));
	}
}
