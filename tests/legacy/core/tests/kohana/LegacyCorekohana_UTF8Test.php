<?php
/**
 * Tests Kohana_UTF8 class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_UTF8Test extends KO8_TestCase
{

	/**
	 * Provides test data for test_clean()
	 */
	public static function provider_clean(){
		return [
			["\0", ''],
			["→foo\021", '→foo'],
			["\x7Fbar", 'bar'],
			["\xFF", ''],
			["\x41", 'A'],
			[["→foo\021", "\x41"], ['→foo', 'A']],
		];
	}

	/**
	 * Tests UTF8::clean
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_clean')]
	public function test_clean($input, $expected)
	{
		$this->assertSame($expected, UTF8::clean($input));
	}

	/**
	 * Provides test data for test_is_ascii()
	 */
	public static function provider_is_ascii(){
		return [
			["\0", TRUE],
			["\$eno\r", TRUE],
			['Señor', FALSE],
			[['Se', 'nor'], TRUE],
			[['Se', 'ñor'], FALSE],
		];
	}

	/**
	 * Tests UTF8::is_ascii
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_is_ascii')]
	public function test_is_ascii($input, $expected)
	{
		$this->assertSame($expected, UTF8::is_ascii($input));
	}

	/**
	 * Provides test data for test_strip_ascii_ctrl()
	 */
	public static function provider_strip_ascii_ctrl(){
		return [
			["\0", ''],
			["→foo\021", '→foo'],
			["\x7Fbar", 'bar'],
			["\xFF", "\xFF"],
			["\x41", 'A'],
		];
	}

	/**
	 * Tests UTF8::strip_ascii_ctrl
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strip_ascii_ctrl')]
	public function test_strip_ascii_ctrl($input, $expected)
	{
		$this->assertSame($expected, UTF8::strip_ascii_ctrl($input));
	}

	/**
	 * Provides test data for test_strip_non_ascii()
	 */
	public static function provider_strip_non_ascii(){
		return [
			["\0\021\x7F", "\0\021\x7F"],
			['I ♥ cocoñùт', 'I  coco'],
		];
	}

	/**
	 * Tests UTF8::strip_non_ascii
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strip_non_ascii')]
	public function test_strip_non_ascii($input, $expected)
	{
		$this->assertSame($expected, UTF8::strip_non_ascii($input));
	}

	/**
	 * Provides test data for test_transliterate_to_ascii()
	 */
	public static function provider_transliterate_to_ascii(){
		return [
			['Cocoñùт', -1, 'Coconuт'],
			['COCOÑÙТ', -1, 'COCOÑÙТ'],
			['Cocoñùт', 0, 'Coconuт'],
			['COCOÑÙТ', 0, 'COCONUТ'],
			['Cocoñùт', 1, 'Cocoñùт'],
			['COCOÑÙТ', 1, 'COCONUТ'],
		];
	}

	/**
	 * Tests UTF8::transliterate_to_ascii
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_transliterate_to_ascii')]
	public function test_transliterate_to_ascii($input, $case, $expected)
	{
		$this->assertSame($expected, UTF8::transliterate_to_ascii($input, $case));
	}

	/**
	 * Provides test data for test_strlen()
	 */
	public static function provider_strlen(){
		return [
			['Cocoñùт', 7],
			['Coconut', 7],
		];
	}

	/**
	 * Tests UTF8::strlen
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strlen')]
	public function test_strlen($input, $expected)
	{
		$this->assertSame($expected, UTF8::strlen($input));
	}

	/**
	 * Provides test data for test_strpos()
	 */
	public static function provider_strpos(){
		return [
			['Cocoñùт', 'o', 0, 1],
			['Cocoñùт', 'ñ', 1, 4],
		];
	}

	/**
	 * Tests UTF8::strpos
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strpos')]
	public function test_strpos($input, $str, $offset, $expected)
	{
		$this->assertSame($expected, UTF8::strpos($input, $str, $offset));
	}

	/**
	 * Provides test data for test_strrpos()
	 */
	public static function provider_strrpos(){
		return [
			['Cocoñùт', 'o', 0, 3],
			['Cocoñùт', 'ñ', 2, 4],
		];
	}

	/**
	 * Tests UTF8::strrpos
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strrpos')]
	public function test_strrpos($input, $str, $offset, $expected)
	{
		$this->assertSame($expected, UTF8::strrpos($input, $str, $offset));
	}

	/**
	 * Provides test data for test_substr()
	 */
	public static function provider_substr(){
		return [
			['Cocoñùт', 3, 2, 'oñ'],
			['Cocoñùт', 3, 9, 'oñùт'],
			['Cocoñùт', 3, NULL, 'oñùт'],
			['Cocoñùт', 3, -2, 'oñ'],
		];
	}

	/**
	 * Tests UTF8::substr
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_substr')]
	public function test_substr($input, $offset, $length, $expected)
	{
		$this->assertSame($expected, UTF8::substr($input, $offset, $length));
	}

	/**
	 * Provides test data for test_substr_replace()
	 */
	public static function provider_substr_replace(){
		return [
			['Cocoñùт', 'šš', 3, 2, 'Cocššùт'],
			['Cocoñùт', 'šš', 3, 9, 'Cocšš'],
		];
	}

	/**
	 * Tests UTF8::substr_replace
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_substr_replace')]
	public function test_substr_replace($input, $replacement, $offset, $length, $expected)
	{
		$this->assertSame($expected, UTF8::substr_replace($input, $replacement, $offset, $length));
	}

	/**
	 * Provides test data for test_strtolower()
	 */
	public static function provider_strtolower(){
		return [
			['COCOÑÙТ', 'cocoñùт'],
			['JÄGER',   'jäger'],
		];
	}

	/**
	 * Tests UTF8::strtolower
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strtolower')]
	public function test_strtolower($input, $expected)
	{
		$this->assertSame($expected, UTF8::strtolower($input));
	}

	/**
	 * Provides test data for test_strtoupper()
	 */
	public static function provider_strtoupper(){
		return [
			['Cocoñùт', 'COCOÑÙТ'],
			['jäger',   'JÄGER'],
		];
	}

	/**
	 * Tests UTF8::strtoupper
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strtoupper')]
	public function test_strtoupper($input, $expected)
	{
		$this->assertSame($expected, UTF8::strtoupper($input));
	}

	/**
	 * Provides test data for test_ucfirst()
	 */
	public static function provider_ucfirst(){
		return [
			['ñùт', 'Ñùт'],
		];
	}

	/**
	 * Tests UTF8::ucfirst
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_ucfirst')]
	public function test_ucfirst($input, $expected)
	{
		$this->assertSame($expected, UTF8::ucfirst($input));
	}

	/**
	 * Provides test data for test_strip_non_ascii()
	 */
	public static function provider_ucwords(){
		return [
			['ExAmple', 'ExAmple'],
			['i ♥ Cocoñùт', 'I ♥ Cocoñùт'],
		];
	}

	/**
	 * Tests UTF8::ucwords
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_ucwords')]
	public function test_ucwords($input, $expected)
	{
		$this->assertSame($expected, UTF8::ucwords($input));
	}

	/**
	 * Provides test data for test_strcasecmp()
	 */
	public static function provider_strcasecmp(){
		return [
			['Cocoñùт',   'Cocoñùт', 0],
			['Čau',       'Čauo',   -1],
			['Čau',       'Ča',      1],
			// strcmp() has returned -1/0/1 since PHP 8.2, not the byte delta.
			['Cocoñùт',   'Cocoñ',   1],
			['Cocoñùт',   'Coco',    1],
		];
	}

	/**
	 * Tests UTF8::strcasecmp
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strcasecmp')]
	public function test_strcasecmp($input, $input2, $expected)
	{
		$this->assertSame($expected, UTF8::strcasecmp($input, $input2));
	}

	/**
	 * Provides test data for test_str_ireplace()
	 */
	public static function provider_str_ireplace(){
		return [
			['т', 't', 'cocoñuт', 'cocoñut'],
			['Ñ', 'N', 'cocoñuт', 'cocoNuт'],
			[['т', 'Ñ', 'k' => 'k'], ['t', 'N', 'K'], ['cocoñuт'], ['cocoNut']],
			[['ñ'], 'n', 'cocoñuт', 'coconuт'],
		];
	}

	/**
	 * Tests UTF8::str_ireplace
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_str_ireplace')]
	public function test_str_ireplace($search, $replace, $subject, $expected)
	{
		$this->assertSame($expected, UTF8::str_ireplace($search, $replace, $subject));
	}

	/**
	 * Provides test data for test_stristr()
	 */
	public static function provider_stristr(){
		return [
			['Cocoñùт',   'oñ', 'oñùт'],
			['Cocoñùт',   'o', 'ocoñùт'],
			['Cocoñùт',   'k', FALSE],
		];
	}

	/**
	 * Tests UTF8::stristr
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_stristr')]
	public function test_stristr($input, $input2, $expected)
	{
		$this->assertSame($expected, UTF8::stristr($input, $input2));
	}

	/**
	 * Provides test data for test_strspn()
	 */
	public static function provider_strspn(){
		return [
			["foo", "o", 1, 2, 2],
			['Cocoñùт', 'oñ', NULL, NULL, 1],
			['Cocoñùт', 'oñ', 2, 4, 1],
			['Cocoñùт', 'šš', 3, 9, 4],
		];
	}

	/**
	 * Tests UTF8::strspn
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strspn')]
	public function test_strspn($input, $mask, $offset, $length, $expected)
	{
		$this->assertSame($expected, UTF8::strspn($input, $mask, $offset, $length));
	}

	/**
	 * Provides test data for test_strcspn()
	 */
	public static function provider_strcspn(){
		return [
			['Cocoñùт', 'oñ', NULL, NULL, 1],
			['Cocoñùт', 'oñ', 2, 4, 1],
			['Cocoñùт', 'šš', 3, 9, 4],
		];
	}

	/**
	 * Tests UTF8::strcspn
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strcspn')]
	public function test_strcspn($input, $mask, $offset, $length, $expected)
	{
		$this->assertSame($expected, UTF8::strcspn($input, $mask, $offset, $length));
	}

	/**
	 * Provides test data for test_str_pad()
	 */
	public static function provider_str_pad(){
		return [
			['Cocoñùт', 10, 'š', STR_PAD_RIGHT, 'Cocoñùтššš'],
			['Cocoñùт', 10, 'š', STR_PAD_LEFT,  'šššCocoñùт'],
			['Cocoñùт', 10, 'š', STR_PAD_BOTH,  'šCocoñùтšš'],
		];
	}

	/**
	 * Tests UTF8::str_pad
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_str_pad')]
	public function test_str_pad($input, $length, $pad, $type, $expected)
	{
		$this->assertSame($expected, UTF8::str_pad($input, $length, $pad, $type));
	}

        /**
	 * Tests UTF8::str_pad error
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_str_pad_error()
	{
		$this->expectException('UTF8_Exception');
		UTF8::str_pad('Cocoñùт', 10, 'š', 15,  'šCocoñùтšš');
	}

	/**
	 * Provides test data for test_str_split()
	 */
	public static function provider_str_split(){
		return [
			['Bár',     1, ['B', 'á', 'r']],
			['Cocoñùт', 2, ['Co', 'co', 'ñù', 'т']],
			['Cocoñùт', 3, ['Coc', 'oñù', 'т']],
		];
	}

	/**
	 * Tests UTF8::str_split
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_str_split')]
	public function test_str_split($input, $split_length, $expected)
	{
		$this->assertSame($expected, UTF8::str_split($input, $split_length));
	}

	/**
	 * Provides test data for test_strrev()
	 */
	public static function provider_strrev(){
		return [
			['Cocoñùт', 'тùñocoC'],
		];
	}

	/**
	 * Tests UTF8::strrev
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_strrev')]
	public function test_strrev($input, $expected)
	{
		$this->assertSame($expected, UTF8::strrev($input));
	}

	/**
	 * Provides test data for test_trim()
	 */
	public static function provider_trim(){
		return [
			[' bar ', NULL, 'bar'],
			['bar',   'b',  'ar'],
			['barb',  'b',  'ar'],
		];
	}

	/**
	 * Tests UTF8::trim
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_trim')]
	public function test_trim($input, $input2, $expected)
	{
		$this->assertSame($expected, UTF8::trim($input, $input2));
	}

	/**
	 * Provides test data for test_ltrim()
	 */
	public static function provider_ltrim(){
		return [
			[' bar ', NULL, 'bar '],
			['bar',   'b',  'ar'],
			['barb',  'b',  'arb'],
			['ñùт',   'ñ',  'ùт'],
		];
	}

	/**
	 * Tests UTF8::ltrim
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_ltrim')]
	public function test_ltrim($input, $charlist, $expected)
	{
		$this->assertSame($expected, UTF8::ltrim($input, $charlist));
	}

	/**
	 * Provides test data for test_rtrim()
	 */
	public static function provider_rtrim(){
		return [
			[' bar ', NULL, ' bar'],
			['bar',   'b',  'bar'],
			['barb',  'b',  'bar'],
			['Cocoñùт',  'т',  'Cocoñù'],
		];
	}

	/**
	 * Tests UTF8::rtrim
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_rtrim')]
	public function test_rtrim($input, $input2, $expected)
	{
		$this->assertSame($expected, UTF8::rtrim($input, $input2));
	}

	/**
	 * Provides test data for test_ord()
	 */
	public static function provider_ord(){
		return [
			['f', 102],
			['ñ', 241],
			['Ñ', 209],
		];
	}

	/**
	 * Tests UTF8::ord
	 *
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_ord')]
	public function test_ord($input, $expected)
	{
		$this->assertSame($expected, UTF8::ord($input));
	}
}
