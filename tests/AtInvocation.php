<?php

/**
 * Invocation-order rule that matches only the nth call to a method.
 *
 * PHPUnit 4/5 shipped `at($index)`; PHPUnit 10 removed it. The inherited suite
 * uses it to declare two different expectations for two consecutive calls, so
 * the behaviour is worth keeping.
 *
 * The counter is per mock object + method, and advances once per method call.
 * PHPUnit passes the same Invocation instance to every matcher of a single
 * call, so "seen" guards against counting it more than once.
 *
 * @package    Ko8
 * @category   Testing
 */
final class KO8_AtInvocation extends PHPUnit\Framework\MockObject\Rule\InvocationOrder
{
	/**
	 * Call counters, keyed by mock object id and method name.
	 *
	 * @var array<string,int>
	 */
	private static array $counters = [];

	/**
	 * Invocations already counted, keyed by invocation object id.
	 *
	 * @var array<int,true>
	 */
	private static array $seen = [];

	public function __construct(private readonly int $index) {}

	public function matches(PHPUnit\Framework\MockObject\Invocation $invocation): bool
	{
		$key = spl_object_id($invocation->object()).'|'.strtolower($invocation->methodName());

		$id = spl_object_id($invocation);

		if ( ! isset(self::$seen[$id]))
		{
			self::$seen[$id] = TRUE;
			self::$counters[$key] = (self::$counters[$key] ?? -1) + 1;
		}

		return self::$counters[$key] === $this->index;
	}

	public function verify(): void
	{
		// This rule describes *when* an expectation applies, not how often.
	}

	public function toString(): string
	{
		return 'invoked at index '.$this->index;
	}
}
