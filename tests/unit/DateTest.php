<?php

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(Date::class)]
final class DateTest extends KO8_TestCase
{
	public static function monthFormatProvider(): array
	{
		return [
			'long'  => [Date::MONTHS_LONG, 12],
			'short' => [Date::MONTHS_SHORT, 12],
		];
	}

	#[DataProvider('monthFormatProvider')]
	public function test_months_returns_twelve_names(string $format, int $expected): void
	{
		$months = Date::months($format);

		$this->assertCount($expected, $months);
		$this->assertArrayHasKey(1, $months);
		$this->assertArrayHasKey(12, $months);
		$this->assertNotSame('', $months[1]);
	}

	public function test_months_long_names_are_longer_than_short(): void
	{
		$long  = Date::months(Date::MONTHS_LONG);
		$short = Date::months(Date::MONTHS_SHORT);

		// September is the pathological case: "September" vs "Sep".
		$this->assertGreaterThan(strlen($short[9]), strlen($long[9]));
	}

	public function test_span(): void
	{
		$span = Date::span(0, 3661, 'hours,minutes,seconds');

		$this->assertSame(['hours' => 1, 'minutes' => 1, 'seconds' => 1], $span);
	}
}
