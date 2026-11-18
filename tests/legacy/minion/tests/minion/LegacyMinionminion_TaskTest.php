<?php

/**
 * Test case for Minion_Util
 *
 * @package    Kohana/Minion
 * @category   Test
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */

class LegacyMinionminion_TaskTest extends KO8_TestCase
{
	/**
	 * Provides test data for test_convert_task_to_class_name()
	 *
	 * @return array
	 */
	public static function provider_convert_task_to_class_name(){
		return [
			['Task_Db_Migrate', 'db:migrate'],
			['Task_Db_Status',  'db:status'],
			['', ''],
		];
	}

	/**
	 * Tests that a task can be converted to a class name
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Minion_Task', 'convert_task_to_class_name')]
	 * @param string Expected class name
	 * @param string Input task name
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_convert_task_to_class_name')]
	public function test_convert_task_to_class_name($expected, $task_name)
	{
		$this->assertSame($expected, Minion_Task::convert_task_to_class_name($task_name));
	}

	/**
	 * Provides test data for test_convert_class_to_task()
	 *
	 * @return array
	 */
	public static function provider_convert_class_to_task(){
		return [
			['db:migrate', 'Task_Db_Migrate'],
		];
	}

	/**
	 * Tests that the task name can be found from a class name / object
	 *
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Minion_Task', 'convert_class_to_task')]
	 * @param string Expected task name
	 * @param mixed  Input class
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_convert_class_to_task')]
	public function test_convert_class_to_task($expected, $class)
	{
		$this->assertSame($expected, Minion_Task::convert_class_to_task($class));
	}
}
