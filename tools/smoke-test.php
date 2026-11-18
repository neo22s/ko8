<?php
declare(strict_types=1);

/**
 * Boot ko8 the way an application boots it, and prove it works.
 *
 * The unit suites cover behaviour class by class. This covers the thing they
 * cannot: that the framework starts, autoloads, routes a request and renders a
 * response on the PHP version it is running on. It is the check that matters on
 * PHP 8.1, where PHPUnit 11 cannot even be installed.
 *
 * Uso: php tools/smoke-test.php
 */
define('EXT', '.php');
define('DOCROOT', dirname(__DIR__).'/');
define('SYSPATH', DOCROOT.'system/');
define('APPPATH', DOCROOT.'application/');
define('MODPATH', DOCROOT.'modules/');
define('KOHANA_START_TIME', microtime(TRUE));
define('KOHANA_START_MEMORY', memory_get_usage());

// Composer's autoloader is optional here: the framework has its own, and this
// script is the one thing that has to run before (or without) a composer
// install. Requiring a missing file turned the PHP 8.1 smoke job into a fatal.
if (is_file(DOCROOT.'vendor/autoload.php'))
{
	require DOCROOT.'vendor/autoload.php';
}

require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

spl_autoload_register(['Kohana', 'auto_load']);

Kohana::init([
	'base_url'  => '/',
	'index_file'=> 'index.php',
	'errors'    => FALSE,
	'profile'   => FALSE,
	'caching'   => FALSE,
]);

Kohana::$config->attach(new Config_File);

Kohana::modules([
	'userguide' => MODPATH.'userguide',
	'database'  => MODPATH.'database',
	'orm'       => MODPATH.'orm',
	'auth'      => MODPATH.'auth',
	'cache'     => MODPATH.'cache',
	'image'     => MODPATH.'image',
	'encrypt'   => MODPATH.'encrypt',
]);

$checks = [];

/**
 * Record a check.
 */
function smoke(array &$checks, string $name, bool $ok, string $detail = ''): void
{
	$checks[] = [$name, $ok, $detail];
}

// 1. The version the framework reports.
smoke($checks, 'version constants',
	Kohana::VERSION === '8.0.0' AND Kohana::CODENAME === 'genesis' AND Kohana::KOSEVEN_COMPAT === '3.3.9',
	Kohana::version());

smoke($checks, 'version() string', str_starts_with(Kohana::version(), 'ko8 8.0.0'), Kohana::version());

// 2. Autoloading, including transparent extension.
smoke($checks, 'autoload Kohana_Arr', class_exists('Arr'));
smoke($checks, 'autoload Arr (transparent)', (new Arr) instanceof Kohana_Arr);
smoke($checks, 'autoload ORM', class_exists('ORM'));
smoke($checks, 'autoload Auth', class_exists('Auth'));

// 3. Routing and a real request.
Route::set('docs/guide', 'guide(/<module>(/<page>))', ['page' => '.+'])
	->defaults(['controller' => 'Userguide', 'action' => 'docs']);
Route::set('default', '(<controller>(/<action>(/<id>)))')
	->defaults(['controller' => 'Welcome', 'action' => 'index']);

$response = Request::factory('guide/kohana/install')->execute();

smoke($checks, 'guide page renders', $response->status() === 200, 'HTTP '.$response->status());
smoke($checks, 'guide page has content', str_contains($response->body(), 'Requirements'));

$body = $response->body();

preg_match_all('/href="([^"]+)"/', $body, $links);

$relative = array_filter($links[1], static fn ($href) => ! preg_match('~^(?:https?://|//|#|mailto:|/index\.php/)~', $href));

smoke($checks, 'guide links are absolute', $relative === [], implode(', ', array_slice($relative, 0, 3)));

// 4. The error page, which is what a broken application sees first.
$html = (string) View::factory('kohana/error', [
	'class'   => 'HTTP_Exception_404',
	'code'    => 404,
	'message' => 'Not found.',
	'file'    => __FILE__,
	'line'    => __LINE__,
	'trace'   => [],
	'error_id'=> 'smoke',
	'is_http' => TRUE,
]);

smoke($checks, 'error page renders', str_contains($html, '<html') AND str_contains($html, 'Not found.'));

// 5. Encryption, which was a fatal before ko8 fixed the default.
try
{
	// A key is application configuration; the smoke test supplies its own so it
	// does not depend on the template having one.
	Kohana::$config->load('encrypt')->set('default', [
		'type'   => 'openssl',
		'key'    => str_repeat('k', 32),
		'cipher' => 'AES-256-CBC',
	]);

	$encrypt = Encrypt::instance();

	$plain = 'smoke test';

	smoke($checks, 'encrypt round trip', $encrypt->decode($encrypt->encode($plain)) === $plain, get_class($encrypt));
}
catch (Throwable $e)
{
	smoke($checks, 'encrypt round trip', FALSE, $e->getMessage());
}

// 6. Configuration loading from all three layers.
smoke($checks, 'config cascades', Kohana::$config->load('database.default.type') === 'MySQLi');

$failed = array_filter($checks, static fn ($check) => ! $check[1]);

printf("ko8 smoke test · PHP %s\n\n", PHP_VERSION);

foreach ($checks AS [$name, $ok, $detail])
{
	printf("  %s %-28s %s\n", $ok ? 'ok  ' : 'FAIL', $name, $detail);
}

printf("\n%d checks, %d failed\n", count($checks), count($failed));

exit($failed ? 1 : 0);
