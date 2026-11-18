<?php

/**
 * Test case for Kohana_ORM
 *
 * @package    Kohana/ORM
 * @category   Test
 * @author     Craig Duncan <git@duncanc.co.uk>
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */

class LegacyOrmORMTest extends KO8_TestCase
{
	/**
	 * Ensure has() doesn't attempt to count non-countables.
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('ORM', 'has')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_has()
	{
		$orm = new ORM_Example;

		$result = $orm->has('children', FALSE);

		$this->assertSame(FALSE, $result);
	}
}

class ORM_Example extends Kohana_ORM
{
	public function __construct()
	{
	}
}
