<?php

/**
 * Unit tests for response class
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_ResponseTest extends KO8_TestCase
{
	/**
	 * Provider for test_body
	 *
	 * @return array
	 */
	public static function provider_body(){
		// PHPUnit 11 cannot configure __toString() on a mock; a real
		// stringable object exercises the same code path in Response.
		$view = self::stringable('foo');

		return [
			['unit test', 'unit test'],
			[$view, 'foo'],
		];
	}

	/**
	 * Tests that we can set and read a body of a response
	 * 
	 *
	 * @return null
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_body')]
	public function test_body($source, $expected)
	{
		$response = new Response;
		$response->body($source);
		$this->assertSame($response->body(), $expected);

		$response = (string) $response;
		$this->assertSame($response, $expected);
	}

	/**
	 * Provides data for test_body_string_zero()
	 *
	 * @return array
	 */
	public static function provider_body_string_zero(){
		return [
			['0', '0'],
			["0", '0'],
			[0, '0']
		];
	}

	/**
	 * Test that Response::body() handles numerics correctly
	 *
	 * @param string $string 
	 * @param string $expected 
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_body_string_zero')]
	public function test_body_string_zero($string, $expected)
	{
		$response = new Response;
		$response->body($string);

		$this->assertSame($expected, $response->body());
	}

	/**
	 * provider for test_cookie_set()
	 *
	 * @return array
	 */
	public static function provider_cookie_set(){
		return [
			[
				'test1',
				'foo',
				[
					'test1' => [
						'value' => 'foo',
						'expiration' => Cookie::$expiration
					],
				]
			],
			[
				[
					'test2' => 'stfu',
					'test3' => [
						'value' => 'snafu',
						'expiration' => 123456789
					]
				],
				NULL,
				[
					'test2' => [
						'value' => 'stfu',
						'expiration' => Cookie::$expiration
					],
					'test3' => [
						'value' => 'snafu',
						'expiration' => 123456789
					]
				]
			]
		];
	}

	/**
	 * Tests the Response::cookie() method, ensures
	 * correct values are set, including defaults
	 *
	 * @param string $key 
	 * @param string $value 
	 * @param string $expected 
	 * @return void
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_cookie_set')]
	public function test_cookie_set($key, $value, $expected)
	{
		// Setup the Response and apply cookie
		$response = new Response;
		$response->cookie($key, $value);

		foreach ($expected as $_key => $_value)
		{
			$cookie = $response->cookie($_key);

			$this->assertSame($_value['value'], $cookie['value']);
			$this->assertSame($_value['expiration'], $cookie['expiration']);
		}
	}

	/**
	 * Tests the Response::cookie() get functionality
	 *
	 * @return void
	 */
	public function test_cookie_get()
	{
		$response = new Response;

		// Test for empty cookies
		$this->assertSame([], $response->cookie());

		// Test for no specific cookie
		$this->assertNull($response->cookie('foobar'));

		$response->cookie('foo', 'bar');
		$cookie = $response->cookie('foo');

		$this->assertSame('bar', $cookie['value']);
		$this->assertSame(Cookie::$expiration, $cookie['expiration']);
	}

	/**
	 * Test the content type is sent when set
	 * 
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_content_type_when_set()
	{
		$content_type = 'application/json';
		$response = new Response;
		$response->headers('content-type', $content_type);
		$headers  = $response->send_headers()->headers();
		$this->assertSame($content_type, (string) $headers['content-type']);
	}
}
