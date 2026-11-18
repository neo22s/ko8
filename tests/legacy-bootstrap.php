<?php
/**
 * Bootstrap for the inherited (2016) suite.
 *
 * Every module's tests are copied under tests/legacy/<suite>/ and registered as
 * a Kohana module, so `Kohana::find_file('tests/...')` keeps resolving the way
 * it did inside the old `unittest` runner.
 */
require __DIR__.'/bootstrap.php';

// A config reader is required before any module init.php runs.
Kohana::$config->attach(new Config_File);

// The modules whose classes the inherited tests exercise. Loading them here is
// what the old `unittest` bootstrap did implicitly.
Kohana::modules([
	'encrypt'      => MODPATH.'encrypt',
	'auth'         => MODPATH.'auth',
	'cache'        => MODPATH.'cache',
	'database'     => MODPATH.'database',
	'image'        => MODPATH.'image',
	'minion'       => MODPATH.'minion',
	'orm'          => MODPATH.'orm',
	'pagination'   => MODPATH.'pagination',
	'userguide'    => MODPATH.'userguide',
]);

Kohana::modules(Kohana::modules() + [
	'legacy-core'  => __DIR__.'/legacy/core',
	'legacy-cache' => __DIR__.'/legacy/cache',
	'legacy-image' => __DIR__.'/legacy/image',
	'legacy-minion'=> __DIR__.'/legacy/minion',
	'legacy-orm'   => __DIR__.'/legacy/orm',
	'legacy-userguide' => __DIR__.'/legacy/userguide',
	// Test fixtures: cache groups, trusted hosts...
	'legacy-fixtures' => __DIR__.'/fixtures',
]);
