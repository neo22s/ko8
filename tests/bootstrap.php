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

// Kohana::init() translates its own errors through __(), which lives at the
// bottom of the I18n class file. Loading I18n now is what the real request
// path does too; defining our own __() would silently shadow it and make every
// translation test fail.
require SYSPATH.'classes/Kohana/I18n'.EXT;

spl_autoload_register(['Kohana', 'auto_load']);

Kohana::init([
	'base_url' => '/',
	'errors'   => FALSE,
	'profile'  => FALSE,
	'caching'  => FALSE,
]);

Kohana::$environment = Kohana::TESTING;

// Module init.php files read their own config while registering routes, so a
// reader has to exist before Kohana::modules() is called.
Kohana::$config->attach(new Config_File);

// Routes are matched in the order they were defined, so the catch-all default
// route must be registered *after* the modules that add their own routes
// (userguide: /guide, /guide-api). Each bootstrap does that at the end.

require __DIR__.'/AtInvocation.php';
require __DIR__.'/TestCase.php';
