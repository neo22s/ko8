<?php

/**
 * Unit tests for internal request client
 *
 *
 * @package    Kohana
 * @category   Tests
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyCorekohana_request_client_InternalTest extends KO8_TestCase
{

	protected $_log_object;

	// @codingStandardsIgnoreStart
	public function setUp(): void // @codingStandardsIgnoreEnd
	{
		parent::setUp();

		// temporarily save $log object
		$this->_log_object = Kohana::$log;
		Kohana::$log = NULL;
	}

	// @codingStandardsIgnoreStart
	public function tearDown(): void // @codingStandardsIgnoreEnd
	{
		// re-assign log object
		Kohana::$log = $this->_log_object;

		parent::tearDown();
	}

	public static function provider_response_failure_status(){
		return [
			['', 'Welcome', 'missing_action', 'Welcome/missing_action', 404],
			['kohana3', 'missing_controller', 'index', 'kohana3/missing_controller/index', 404],
			['', 'Template', 'missing_action', 'kohana3/Template/missing_action', 500],
		];
	}

	/**
	 * Tests for correct exception messages
	 *
	 *
	 * @return null
	 */
	#[\PHPUnit\Framework\Attributes\Test]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_response_failure_status')]
	public function test_response_failure_status($directory, $controller, $action, $uri, $expected)
	{
		// Mock for request object
		$request = $this->mock('Request', ['directory', 'controller', 'action', 'uri', 'response', 'method', 'execute'], FALSE, [$uri], NULL);

		$request->expects($this->any())
			->method('directory')
			->will($this->returnValue($directory));

		$request->expects($this->any())
			->method('controller')
			->will($this->returnValue($controller));

		$request->expects($this->any())
			->method('action')
			->will($this->returnValue($action));

		$request->expects($this->any())
			->method('uri')
			->will($this->returnValue($uri));

		$request->expects($this->any())
			->method('execute')
			->will($this->returnValue($this->mock('Response', ['directory', 'controller', 'action', 'uri', 'execute', 'method'], FALSE, [], NULL)));

		// mock `method` method to avoid fatals in newer versions of PHPUnit
		$request->expects($this->any())
			->method('method')
			->withAnyParameters();

		$internal_client = new Request_Client_Internal;

		$response = $internal_client->execute($request);

		$this->assertSame($expected, $response->status());
	}
}
