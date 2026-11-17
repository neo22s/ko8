<?php
/**
 * Aplica los parches de compatibilidad PHP 8 de ko8.
 *
 * Idempotente: se puede ejecutar varias veces. Falla si un anclaje no existe,
 * para que nunca pase desapercibido un cambio de contexto.
 *
 * Uso: php tools/apply-php8-patches.php
 */

$root = dirname(__DIR__);

/**
 * Sustituye un bloque exacto, una sola vez.
 */
function ko8_patch(string $path, string $search, string $replace, string $label): void
{
	global $root;

	$full = $root.'/'.$path;

	if ( ! is_file($full))
	{
		fwrite(STDERR, "MISSING FILE: $path\n");
		exit(1);
	}

	$source = file_get_contents($full);

	if ($replace !== '' AND str_contains($source, $replace))
	{
		echo "  already applied  $label\n";

		return;
	}

	if ( ! str_contains($source, $search))
	{
		fwrite(STDERR, "ANCHOR NOT FOUND: $label ($path)\n");
		exit(1);
	}

	file_put_contents($full, str_replace($search, $replace, $source));

	echo "  patched          $label\n";
}

echo "ko8 · PHP 8 compatibility patches\n\n";

// 1. Arr::path() must accept ArrayObject (Config_Group).
ko8_patch(
	'system/classes/Kohana/Arr.php',
	"\t\t\tif (array_key_exists(\$path, \$array))",
	"\t\t\t// isset() instead of array_key_exists(): \$array may be an ArrayObject\n"
	."\t\t\t// (Config_Group), and array_key_exists() requires a real array in PHP 8.\n"
	."\t\t\tif (isset(\$array[\$path]))",
	'Arr::path() accepts Config_Group'
);

// 2. E_STRICT is deprecated since PHP 8.4.
ko8_patch(
	'system/classes/Kohana/Kohana/Exception.php',
	"        E_STRICT             => 'Strict',\n",
	'',
	'E_STRICT removed from the error map'
);

// 3. strftime() is deprecated since PHP 8.1.
ko8_patch(
	'system/classes/Kohana/Date.php',
	"\t\t\tfor (\$i = 1; \$i <= 12; ++\$i)\n"
	."\t\t\t{\n"
	."\t\t\t\t\$months[\$i] = strftime(\$format, mktime(0, 0, 0, \$i, 1));\n"
	."\t\t\t}",
	"\t\t\t// strftime() is deprecated since PHP 8.1. IntlDateFormatter honours the\n"
	."\t\t\t// current locale; DateTime::format() is the locale-independent fallback.\n"
	."\t\t\t\$intl = NULL;\n\n"
	."\t\t\tif (class_exists('IntlDateFormatter'))\n"
	."\t\t\t{\n"
	."\t\t\t\t\$intl = new IntlDateFormatter(\n"
	."\t\t\t\t\tclass_exists('Locale') ? Locale::getDefault() : setlocale(LC_TIME, '0'),\n"
	."\t\t\t\t\tIntlDateFormatter::NONE,\n"
	."\t\t\t\t\tIntlDateFormatter::NONE,\n"
	."\t\t\t\t\t'UTC',\n"
	."\t\t\t\t\tIntlDateFormatter::GREGORIAN,\n"
	."\t\t\t\t\t(\$format === Date::MONTHS_LONG) ? 'LLLL' : 'LLL'\n"
	."\t\t\t\t);\n"
	."\t\t\t}\n\n"
	."\t\t\tfor (\$i = 1; \$i <= 12; ++\$i)\n"
	."\t\t\t{\n"
	."\t\t\t\t\$date = new DateTime(sprintf('2000-%02d-01', \$i), new DateTimeZone('UTC'));\n\n"
	."\t\t\t\t\$months[\$i] = \$intl\n"
	."\t\t\t\t\t? \$intl->format(\$date)\n"
	."\t\t\t\t\t: \$date->format((\$format === Date::MONTHS_LONG) ? 'F' : 'M');\n"
	."\t\t\t}",
	'Date::months() without strftime()'
);

// 4. utf8_decode() is deprecated since PHP 8.2, removed in PHP 9.
ko8_patch(
	'system/utf8/strlen.php',
	"\treturn strlen(utf8_decode(\$str));",
	"\t// utf8_decode() is deprecated in PHP 8.2 and removed in PHP 9.\n"
	."\treturn mb_strlen(\$str, 'UTF-8');",
	'UTF8::strlen() without utf8_decode()'
);

// 5. PHP 8.1 enables mysqli exceptions by default; the driver expects FALSE.
$mysqli = 'modules/database/classes/Kohana/Database/MySQLi.php';

ko8_patch(
	$mysqli,
	"\t\tif (\$this->_connection)\n\t\t\treturn;\n\n\t\tif (Database_MySQLi::\$_set_names === NULL)",
	"\t\tif (\$this->_connection)\n\t\t\treturn;\n\n"
	."\t\t// PHP 8.1+ enables MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT by default.\n"
	."\t\t// The driver, and every app written for it, relies on the legacy contract:\n"
	."\t\t// mysqli_query() returns FALSE and the error is read from \$connection->error.\n"
	."\t\tif (function_exists('mysqli_report'))\n"
	."\t\t{\n"
	."\t\t\tmysqli_report(MYSQLI_REPORT_OFF);\n"
	."\t\t}\n\n"
	."\t\tif (Database_MySQLi::\$_set_names === NULL)",
	'MySQLi error reporting back to legacy'
);

ko8_patch($mysqli, "if (is_resource(\$this->_connection))", "if (\$this->_connection instanceof mysqli)", 'MySQLi disconnect (object check)');
ko8_patch($mysqli, "\$status = ! is_resource(\$this->_connection);", "\$status = ! (\$this->_connection instanceof mysqli);", 'MySQLi disconnect fallback (object check)');

ko8_patch(
	$mysqli,
	"\t\tcatch (Exception \$e)\n\t\t{\n\t\t\t// No connection exists",
	"\t\tcatch (Throwable \$e)\n\t\t{\n\t\t\t// No connection exists",
	'MySQLi connect() catches Throwable'
);

echo "\nDone. Review the diff with: git diff\n";
