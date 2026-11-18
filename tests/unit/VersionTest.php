<?php

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Kohana::class)]
final class VersionTest extends KO8_TestCase
{
	/**
	 * The framework has its own version, and it is not Koseven's. Two projects
	 * answering with the same string is a support problem: an error report would
	 * not say which codebase produced it.
	 */
	public function test_version_is_ko8s_own(): void
	{
		$this->assertSame('8.0.0', Kohana::VERSION);
		$this->assertSame('genesis', Kohana::CODENAME);
	}

	/**
	 * The compatibility constant is the one code should compare: it states which
	 * Koseven release this API implements, which is what an application actually
	 * cares about.
	 */
	public function test_koseven_compat_records_the_api_implemented(): void
	{
		$this->assertSame('3.3.9', Kohana::KOSEVEN_COMPAT);
		$this->assertTrue(version_compare(Kohana::KOSEVEN_COMPAT, '3.3.0', '>='));
	}

	public function test_version_string_identifies_the_framework(): void
	{
		$this->assertSame('ko8 8.0.0 (genesis)', Kohana::version());
		$this->assertStringStartsWith('ko8 ', Kohana::version());
	}
}
