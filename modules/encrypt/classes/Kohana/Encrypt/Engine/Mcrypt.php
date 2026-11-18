<?php
/**
 * The Encrypt Mcrypt engine.
 *
 * ext/mcrypt was removed in PHP 7.2, and with it the MCRYPT_* constants. This
 * class is kept as a tombstone: old applications that name it get a clear
 * exception instead of a fatal "undefined constant".
 *
 * If you need to read data that was encrypted with mcrypt, note that
 * `MCRYPT_RIJNDAEL_128` + `MCRYPT_MODE_NOFB` is exactly `AES-128-OFB`, and the
 * old payload format was `base64(iv . ciphertext)`.
 *
 * @package    Kohana/Encrypt
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 *
 * @deprecated  since 8.0, use the OpenSSL or Sodium engine
 */
class Kohana_Encrypt_Engine_Mcrypt extends Kohana_Encrypt_Engine {

	/**
	 * @throws  Kohana_Exception  always
	 */
	public function __construct($key_config = NULL, $mode = NULL, $cipher = NULL)
	{
		throw new Kohana_Exception(
			'The Mcrypt engine is not available: ext/mcrypt was removed in PHP 7.2. '
			.'Use the OpenSSL or Sodium engine. To read data encrypted with mcrypt, '
			.'use AES-128-OFB with the old base64(iv . ciphertext) payload.'
		);
	}

}
