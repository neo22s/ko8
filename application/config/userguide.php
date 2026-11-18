<?php defined('SYSPATH') or die('No direct script access.');

/**
 * ko8 userguide configuration.
 *
 * The module list is built from every enabled module's own
 * `config/userguide.php`, so there is nothing to list here by hand.
 */
return [
	// Show the API browser (reflection over every loaded class).
	'api_browser' => TRUE,

	// Packages shown in the API browser: TRUE for all.
	'api_packages' => TRUE,

	// Disqus comments are gone; keep the key so old configs do not break.
	'show_comments' => FALSE,

	// Transparent extension prefix: `Kohana_Foo` is documented as `Foo`.
	'transparent_prefixes' => [
		'Kohana' => TRUE,
	],
];
