<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The API browser is reflection over every loaded class: the fastest way to
 * find a dynamic property or a PHP 8 deprecation in the framework.
 *
 * These tests render real pages through the request stack.
 */
#[CoversClass(Kodoc::class)]
final class ApiBrowserTest extends KO8_TestCase
{
	/**
	 * Modules the browser needs.
	 */
	private const MODULES = [
		'auth'      => 'auth',
		'cache'     => 'cache',
		'database'  => 'database',
		'image'     => 'image',
		'orm'       => 'orm',
		'pagination'=> 'pagination',
		'userguide' => 'userguide',
	];

	protected function setUp(): void
	{
		parent::setUp();

		$modules = [];

		foreach (self::MODULES AS $name => $directory)
		{
			$modules[$name] = MODPATH.$directory;
		}

		Kohana::modules($modules);

		// The userguide module registers /guide and /guide-api; the catch-all
		// default route has to come last or it swallows them.
		Route::set('default', '(<controller>(/<action>(/<id>)))')
			->defaults([
				'controller' => 'welcome',
				'action'     => 'index',
			]);
	}

	protected function tearDown(): void
	{
		Kohana::modules([]);

		parent::tearDown();
	}

	public static function classProvider(): array
	{
		return [
			'transparent extension' => ['Arr'],
			'exception hierarchy'   => ['HTTP_Exception_404'],
			'static properties'     => ['Kohana'],
			'documented class'      => ['Request'],
			'deep inheritance'      => ['Response'],
			// This one crashed on a dynamic property in PHP 8.2.
			'exception with properties' => ['HTTP_Exception'],
			'class with constants'      => ['HTTP'],
			'iterator implementation'   => ['Validation'],
		];
	}

	#[DataProvider('classProvider')]
	public function test_class_page_renders(string $class): void
	{
		$response = Request::factory('guide-api/'.$class)->execute();

		$this->assertSame(200, $response->status());

		$body = $response->body();

		$this->assertStringContainsString($class, $body);
		$this->assertStringNotContainsString('Creation of dynamic property', $body);
		$this->assertStringNotContainsString('is deprecated', $body);
	}

	public function test_index_lists_classes(): void
	{
		$response = Request::factory('guide-api')->execute();

		$this->assertSame(200, $response->status());
		$this->assertStringContainsString('guide-api/Arr', $response->body());
	}

	public function test_guide_index_renders(): void
	{
		$response = Request::factory('guide')->execute();

		$this->assertSame(200, $response->status());
		$this->assertStringContainsString('User guide', $response->body());
	}

	public function test_guide_page_renders_markdown(): void
	{
		$response = Request::factory('guide/kohana/install')->execute();

		$this->assertSame(200, $response->status());
		$this->assertStringContainsString('<h1', $response->body());
		$this->assertStringContainsString('Requirements', $response->body());
	}
}
