<?php

/**
 * This test only really exists for code coverage.
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_ModelTest extends KO8_TestCase
{
	/**
	 * Test the model's factory.
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Model', 'factory')]
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	public function test_create()
	{
		$foobar = Model::factory('Foobar');

		$this->assertEquals(TRUE, $foobar instanceof Model);
	}
}

class Model_Foobar extends Model
{

}
