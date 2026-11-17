<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Arr::class)]
final class ArrTest extends KO8_TestCase
{
	public static function pathProvider(): array
	{
		return [
			'simple'     => [['a' => ['b' => 1]], 'a.b', 1],
			'array path' => [['a' => ['b' => 2]], ['a', 'b'], 2],
			'wildcard'   => [['a' => [['n' => 1], ['n' => 2]]], 'a.*.n', [1, 2]],
			'nested'     => [['a' => ['b' => ['c' => 3]]], 'a.b.c', 3],
		];
	}

	#[DataProvider('pathProvider')]
	public function test_path_resolves(array $array, mixed $path, mixed $expected): void
	{
		$this->assertSame($expected, Arr::path($array, $path));
	}

	public function test_path_returns_default(): void
	{
		$this->assertNull(Arr::path(['a' => []], 'a.b'));
		$this->assertSame('fallback', Arr::path(['a' => []], 'a.b', 'fallback'));
	}

	/**
	 * Regression: PHP 8 rejected array_key_exists() on an ArrayObject, which is
	 * exactly what Kohana::$config->load() returns (Config_Group). Every page
	 * that read a dotted config key crashed.
	 */
	public function test_path_accepts_config_group(): void
	{
		$group = new Config_Group(new Config, 'test', ['a' => ['b' => 42]]);

		$this->assertSame(42, Arr::path($group, 'a.b'));
	}

	public function test_get(): void
	{
		$this->assertSame(1, Arr::get(['a' => 1], 'a'));
		$this->assertSame('x', Arr::get([], 'a', 'x'));
		$this->assertNull(Arr::get(['a' => NULL], 'a'));
	}

	public function test_is_array_accepts_traversable(): void
	{
		$this->assertTrue(Arr::is_array([]));
		$this->assertTrue(Arr::is_array(new ArrayObject([])));
		$this->assertTrue(Arr::is_array(new Config_Group(new Config, 'x', [])));
		$this->assertFalse(Arr::is_array('nope'));
		$this->assertFalse(Arr::is_array(new stdClass));
	}
}
