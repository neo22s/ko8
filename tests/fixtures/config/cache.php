<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Cache groups used by the inherited suite.
 *
 * modules/cache/config/cache.php ships entirely commented out, so without this
 * fixture those tests have no `file` group to talk to.
 */
return [
	'default' => 'file',
	'file'    => [
		'driver'           => 'file',
		'cache_dir'        => APPPATH.'cache',
		'default_expire'   => 3600,
		'ignore_on_delete' => [
			'.gitignore',
			'.git',
			'.svn',
			'file_we_want_to_keep.cache',
		],
	],
];
