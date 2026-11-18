<?php

/**
 * Tests Kohana i18n class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     Jeremy Bush <contractfrombelow@gmail.com>
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_I18nTest extends KO8_TestCase {

	/**
	 * Default values for the environment, see setEnvironment
	 * @var array
	 */
	// @codingStandardsIgnoreStart
	protected $environmentDefault =	[
		'I18n::$lang' => 'en-us',
	];
	// @codingStandardsIgnoreEnd

	/**
	 * Provides test data for test_lang()
	 *
	 * @return array
	 */
	public static function provider_lang(){
		return [
			// $input, $expected_result
			[NULL, 'en-us'],
			['es-es', 'es-es'],
		];
	}

	/**
	 * Tests I18n::lang()
	 *
	 * @param  boolean  $input     Input for I18n::lang
	 * @param  boolean  $expected  Output for I18n::lang
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_lang')]
	public function test_lang($input, $expected_result)
	{
		$this->assertSame($expected_result, I18n::lang($input));
		$this->assertSame($expected_result, I18n::lang());
	}

	/**
	 * Provides test data for test_get()
	 * 
	 * @return array
	 */
	public static function provider_get(){
		return [
			// $value, $result
			['en-us', 'Hello, world!', 'Hello, world!'],
			['es-es', 'Hello, world!', '¡Hola, mundo!'],
			['fr-fr', 'Hello, world!', 'Bonjour, monde!'],
		];
	}

	/**
	 * Tests i18n::get()
	 *
	 * @param boolean $input  Input for File::mime
	 * @param boolean $expected Output for File::mime
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_get')]
	public function test_get($lang, $input, $expected)
	{
		I18n::lang($lang);
		$this->assertSame($expected, I18n::get($input));

		// Test immediate translation, issue #3085
		I18n::lang('en-us');
		$this->assertSame($expected, I18n::get($input, $lang));
	}

}
