<?php
/**
 * UTF8::strlen
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function _strlen($str)
{
	if (UTF8::is_ascii($str))
		return strlen($str);

	// utf8_decode() is deprecated in PHP 8.2 and removed in PHP 9.
	return mb_strlen($str, 'UTF-8');
}
