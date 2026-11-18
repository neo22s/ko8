<?php defined('SYSPATH') or die('No direct script access.');

/**
 * URL configuration.
 *
 * Koseven (and ko8) refuse to build absolute URLs for hosts that are not
 * listed here. `URL::base()` with no argument uses this list to validate the
 * incoming Host header, which is what stops host-header poisoning.
 *
 * Add your own hosts. Patterns are regexes and must match the whole host, so
 * escape your dots.
 */
return [

	'trusted_hosts' => [
		'localhost',
		'localhost:\d+',
		'127\.0\.0\.1',
		'127\.0\.0\.1:\d+',
		'.*\.test',
		'.*\.test:\d+',
		'.*\.localhost',
		'.*\.localhost:\d+',
	],

];
