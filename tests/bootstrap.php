<?php
/**
 * ko8 test bootstrap.
 *
 * Deliberately small: no `unittest` module, no web UI, no module cascade. A
 * test suite that needs the framework to serve itself is a suite nobody runs.
 */
define('DOCROOT', dirname(__DIR__).DIRECTORY_SEPARATOR);
define('APPPATH', DOCROOT.'tests/app/');
define('MODPATH', DOCROOT.'modules/');
define('SYSPATH', DOCROOT.'system/');
define('EXT', '.php');

define('KOHANA_START_TIME', microtime(TRUE));
define('KOHANA_START_MEMORY', memory_get_usage());

error_reporting(E_ALL);

require DOCROOT.'vendor/autoload.php';
require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

// Kohana::init() translates its own errors through __(), which normally comes
// from I18n. In CLI there is no I18n yet, so provide a minimal version.
if ( ! function_exists('__'))
{
	function __($string, ?array $values = NULL, $lang = NULL)
	{
		return empty($values) ? $string : strtr($string, $values);
	}
}

spl_autoload_register(['Kohana', 'auto_load']);

Kohana::init([
	'base_url' => '/',
	'errors'   => FALSE,
	'profile'  => FALSE,
	'caching'  => FALSE,
]);

Kohana::$environment = Kohana::TESTING;

require __DIR__.'/TestCase.php';
