<?php

return [

	'default' => [
		/**
		 * The engine to use. `openssl` is the only one that exists in modern
		 * PHP: ext/mcrypt was removed in PHP 7.2, and its cipher and mode
		 * constants are undefined, which is a fatal error.
		 *
		 * string   type    engine: openssl or sodium
		 * string   key     secret passphrase (32 bytes for AES-256-CBC)
		 * string   cipher  OpenSSL cipher name
		 */
		'type'   => 'openssl',
		'cipher' => 'AES-256-CBC',
	],

];
