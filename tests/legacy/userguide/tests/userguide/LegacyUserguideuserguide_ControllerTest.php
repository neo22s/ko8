<?php

/**
 * Unit tests for internal methods of userguide controller
 *
 *
 * @package    Kohana/Userguide
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyUserguideuserguide_ControllerTest extends KO8_TestCase
{

	public static function provider_file_finds_markdown_files(){
		return [
			['userguide/adding', 'guide/userguide/adding.md'],
			['userguide/adding.md', 'guide/userguide/adding.md'],
			['userguide/adding.markdown', 'guide/userguide/adding.md'],
			['userguide/does_not_exist.md', FALSE]
		];
	}

	/**
	 * @param  string  $page           Page name passed in the URL
	 * @param  string  $expected_file  Expected result from Controller_Userguide::file
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_file_finds_markdown_files')]
	public function test_file_finds_markdown_files($page, $expected_file)
	{
		$controller = $this->mock('Controller_Userguide', [], FALSE, [], NULL);

		$cache_reflection = new ReflectionClass('Controller_Userguide');
		$file_method = $cache_reflection->getMethod('file');

		$path = $file_method->invoke($controller, $page);

		// Only verify trailing segments to avoid problems if file overwritten in CFS
		$expected_len = strlen($expected_file);
		$file = substr($path, -$expected_len, $expected_len);

		$this->assertEquals($expected_file, $file);
	}

}
