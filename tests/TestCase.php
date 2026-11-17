<?php

/**
 * Base class for ko8 tests.
 *
 * This replaces the old `Unittest_TestCase`. It keeps the three helpers that
 * were actually used across the suite (environment overrides, temp cache
 * cleanup, path separator) and drops the rest: mocking is PHPUnit's job now.
 *
 * @package    Ko8
 * @category   Testing
 */
abstract class KO8_TestCase extends PHPUnit\Framework\TestCase
{
	/**
	 * Environment overrides applied in setUp(), restored in tearDown().
	 *
	 * Supported keys:
	 *  - `_GET`, `_POST`, `_SERVER`, `_COOKIE`, `_FILES`: superglobals
	 *  - `Kohana::$prop`: static properties
	 *  - `group.key`:    config values
	 *
	 * @var array
	 */
	protected array $environmentDefault = [];

	/**
	 * Backups taken by setEnvironment().
	 *
	 * @var array
	 */
	private array $environmentBackup = [];

	protected function setUp(): void
	{
		parent::setUp();

		if ($this->environmentDefault)
		{
			$this->setEnvironment($this->environmentDefault);
		}
	}

	protected function tearDown(): void
	{
		$this->restoreEnvironment();

		Kohana::$config = NULL;

		parent::tearDown();
	}

	/**
	 * Override environment values for the duration of one test.
	 */
	public function setEnvironment(array $environment): void
	{
		foreach ($environment as $option => $value)
		{
			$backup = ! array_key_exists($option, $this->environmentBackup);

			if (in_array($option, ['_GET', '_POST', '_SERVER', '_COOKIE', '_FILES'], TRUE))
			{
				if ($backup)
				{
					$this->environmentBackup[$option] = $GLOBALS[$option] ?? NULL;
				}

				$GLOBALS[$option] = $value;

				continue;
			}

			if (str_starts_with($option, 'Kohana::$'))
			{
				$property = substr($option, strlen('Kohana::$'));

				if ($backup)
				{
					$this->environmentBackup[$option] = Kohana::$$property ?? NULL;
				}

				Kohana::$$property = $value;

				continue;
			}

			// Dotted key: config group and path.
			if ($backup)
			{
				$this->environmentBackup[$option] = Kohana::$config->load($option);
			}

			[$group, $path] = array_pad(explode('.', $option, 2), 2, NULL);

			$config = Kohana::$config->load($group);

			if ($path === NULL)
			{
				$config->exchangeArray($value);
			}
			else
			{
				$config->set($path, $value);
			}
		}
	}

	/**
	 * Restore whatever setEnvironment() replaced.
	 */
	private function restoreEnvironment(): void
	{
		foreach (array_reverse($this->environmentBackup, TRUE) as $option => $value)
		{
			if (in_array($option, ['_GET', '_POST', '_SERVER', '_COOKIE', '_FILES'], TRUE))
			{
				$GLOBALS[$option] = $value;

				continue;
			}

			if (str_starts_with($option, 'Kohana::$'))
			{
				$property = substr($option, strlen('Kohana::$'));

				Kohana::$$property = $value;

				continue;
			}

			// Restoring a whole group: rewrite every key that was backed up.
			$group = Kohana::$config->load($option);

			if (is_array($value))
			{
				$group->exchangeArray($value);
			}
		}

		$this->environmentBackup = [];
	}

	/**
	 * Empty Kohana's file cache directory.
	 */
	public function cleanCacheDir(): void
	{
		$cache_dir = Kohana::$cache_dir;

		if ( ! is_dir($cache_dir))
		{
			return;
		}

		foreach (glob($cache_dir.'/*', GLOB_ONLYDIR) AS $dir)
		{
			foreach (glob($dir.'/*') AS $file)
			{
				unlink($file);
			}

			rmdir($dir);
		}
	}

	/**
	 * Path with the OS directory separator.
	 */
	public function dirSeparator(string $path): string
	{
		return str_replace('/', DIRECTORY_SEPARATOR, $path);
	}
}
