<?php

/**
 * Tests the Minion library
 *
 *
 * @package    Koseven
 * @category   Tests
 * @author     Koseven Team
 * @author     Piotr Gołasz <pgolasz@gmail.com>
 * @copyright  (c) Koseven Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_MinionTaskTest extends KO8_TestCase {

	/**
	 * Tests that Minion Task Help works assuming all other tasks work aswell
	 */
	public function test_minion_runnable()
	{
		$minion_response = Minion_Task::factory(['task' => 'help']);
		$this->assertInstanceOf('Task_Help', $minion_response);
	}
}
