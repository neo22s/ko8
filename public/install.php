<?php
/**
 * ko8 environment checker.
 *
 * Standalone on purpose: it has to run before the framework is configured and
 * without any dependency on Composer, sessions or the request stack.
 *
 * Open /install.php once, fix what it reports, then delete it.
 *
 * @package    Ko8
 */
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>ko8 · environment check</title>
<style><?php
// Inline the compiled stylesheet: this page has to work even when static
// assets are not being served yet (often the very problem being diagnosed).
$css = __DIR__.'/../system/media/css/ko8.css';
echo is_readable($css) ? file_get_contents($css) : '';
?></style>
</head>
<body class="min-h-full bg-slate-950 text-slate-200 antialiased">

<header class="border-b border-slate-800 bg-slate-900/70">
	<div class="mx-auto flex max-w-4xl items-center gap-3 px-5 py-4">
		<span class="flex items-center gap-2 font-mono text-lg font-bold tracking-tight text-white">
			<span class="grid h-7 w-7 place-items-center rounded-md bg-ko8-600 text-sm text-white">k8</span>
			ko8
		</span>
		<span class="font-mono text-xs text-slate-500">environment check</span>
	</div>
</header>

<main class="mx-auto max-w-4xl px-5 py-8">

<?php
$checks = [];

/**
 * One requirement row.
 */
function ko8_check(array &$checks, string $label, bool $ok, $value, ?string $hint = NULL): void
{
	$checks[] = ['label' => $label, 'ok' => $ok, 'value' => $value, 'hint' => $hint];
}

$root = dirname(__DIR__);
$sys  = $root.'/system';
$app  = $root.'/application';

ko8_check($checks, 'PHP version', version_compare(PHP_VERSION, '8.1', '>='), PHP_VERSION, 'ko8 requires PHP 8.1 or newer.');
ko8_check($checks, 'System directory', is_dir($sys), $sys, 'The system directory is missing.');
ko8_check($checks, 'Core class', is_file($sys.'/classes/Kohana/Core.php'), $sys.'/classes/Kohana/Core.php', 'Kohana/Core.php is missing from the system directory.');
ko8_check($checks, 'Application directory', is_dir($app), $app, 'The application directory is missing.');
ko8_check($checks, 'Bootstrap file', is_file($app.'/bootstrap.php'), $app.'/bootstrap.php', 'bootstrap.php is missing from the application directory.');
ko8_check($checks, 'Composer autoload', is_file($root.'/vendor/autoload.php'), $root.'/vendor/autoload.php', 'Run <code>composer install</code>.');
ko8_check($checks, 'Cache directory writable', is_dir($app.'/cache') AND is_writable($app.'/cache'), $app.'/cache', 'Create the directory and make it writable by the web server.');
ko8_check($checks, 'Logs directory writable', is_dir($app.'/logs') AND is_writable($app.'/logs'), $app.'/logs', 'Create the directory and make it writable by the web server.');
ko8_check($checks, 'mbstring', extension_loaded('mbstring'), extension_loaded('mbstring') ? 'loaded' : 'missing', 'ko8 requires mbstring; the bundled UTF-8 polyfills are gone.');
ko8_check($checks, 'PCRE UTF-8', @preg_match('/^.$/u', 'ñ') === 1, @preg_match('/^.$/u', 'ñ') === 1 ? 'supported' : 'missing');
ko8_check($checks, 'PCRE Unicode properties', @preg_match('/^\pL$/u', 'ñ') === 1, @preg_match('/^\pL$/u', 'ñ') === 1 ? 'supported' : 'missing');
ko8_check($checks, 'SPL', function_exists('spl_autoload_register'), 'loaded');
ko8_check($checks, 'Reflection', class_exists('ReflectionClass'), 'loaded');
ko8_check($checks, 'Filter', function_exists('filter_list'), 'loaded');
ko8_check($checks, 'ctype', function_exists('ctype_digit'), 'loaded');
ko8_check($checks, 'JSON', function_exists('json_encode'), 'loaded');
ko8_check($checks, 'URI detection',
	isset($_SERVER['REQUEST_URI']) OR isset($_SERVER['PHP_SELF']) OR isset($_SERVER['PATH_INFO']),
	isset($_SERVER['REQUEST_URI']) ? 'REQUEST_URI' : (isset($_SERVER['PHP_SELF']) ? 'PHP_SELF' : (isset($_SERVER['PATH_INFO']) ? 'PATH_INFO' : 'none')));

$failed = FALSE;
foreach ($checks as $check)
{
	if ( ! $check['ok'])
	{
		$failed = TRUE;
		break;
	}
}

$optional = [
	'curl'      => 'The external request client',
	'mysqli'    => 'The Database module (MySQL)',
	'pdo'       => 'The Database module (PDO drivers)',
	'gd'        => 'The Image module (GD driver)',
	'imagick'   => 'The Image module (Imagick driver)',
	'intl'      => 'Locale-aware dates and money formatting',
	'openssl'   => 'The Encrypt module',
	'redis'     => 'The Cache module (Redis driver)',
	'memcached' => 'The Cache module (Memcached driver)',
];
?>

	<?php if ($failed): ?>
	<div class="rounded-xl border border-rose-900 bg-rose-950/40 p-5">
		<div class="flex items-start gap-3">
			<span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-rose-500/20 font-mono text-sm text-rose-300">!</span>
			<div>
				<h1 class="font-semibold text-white">This environment is not ready for ko8</h1>
				<p class="mt-1 text-sm text-rose-200/80">Fix the failing checks below and reload this page.</p>
			</div>
		</div>
	</div>
	<?php else: ?>
	<div class="rounded-xl border border-emerald-900 bg-emerald-950/40 p-5">
		<div class="flex items-start gap-3">
			<span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-500/20 font-mono text-sm text-emerald-300">✓</span>
			<div>
				<h1 class="font-semibold text-white">This environment can run ko8</h1>
				<p class="mt-1 text-sm text-emerald-200/80">Delete <code class="rounded bg-emerald-500/10 px-1.5 py-0.5 font-mono text-xs">install.php</code> now.</p>
			</div>
		</div>
	</div>
	<?php endif ?>

	<div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
		<div class="border-b border-slate-800 bg-slate-900 px-4 py-2.5">
			<h2 class="text-sm font-semibold text-slate-200">Requirements</h2>
		</div>
		<table class="w-full border-collapse text-left text-sm">
			<tbody class="divide-y divide-slate-800">
			<?php foreach ($checks as $check): ?>
				<tr class="align-top">
					<th class="w-52 py-3 pl-4 pr-3 font-normal text-slate-400"><?php echo $check['label'] ?></th>
					<td class="py-3 pr-4">
						<div class="flex items-start gap-2">
							<span class="mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full text-[10px] <?php echo $check['ok'] ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' ?>"><?php echo $check['ok'] ? '✓' : '✕' ?></span>
							<span class="min-w-0">
								<span class="break-all font-mono text-xs <?php echo $check['ok'] ? 'text-slate-300' : 'text-rose-300' ?>"><?php echo $check['value'] ?></span>
								<?php if ( ! $check['ok'] AND ! empty($check['hint'])): ?>
									<span class="mt-1 block text-xs text-slate-500"><?php echo $check['hint'] ?></span>
								<?php endif ?>
							</span>
						</div>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>

	<div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
		<div class="border-b border-slate-800 bg-slate-900 px-4 py-2.5">
			<h2 class="text-sm font-semibold text-slate-200">Optional extensions</h2>
			<p class="mt-0.5 text-xs text-slate-500">Not required by the core. Each one unlocks a module or a driver.</p>
		</div>
		<ul class="divide-y divide-slate-800">
		<?php foreach ($optional as $ext => $label): $ok = extension_loaded($ext); ?>
			<li class="flex items-center gap-3 px-4 py-2.5">
				<span class="grid h-4 w-4 shrink-0 place-items-center rounded-full text-[10px] <?php echo $ok ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-700/50 text-slate-500' ?>"><?php echo $ok ? '✓' : '·' ?></span>
				<span class="font-mono text-xs text-slate-300"><?php echo $ext ?></span>
				<span class="truncate text-xs text-slate-500"><?php echo $label ?></span>
			</li>
		<?php endforeach ?>
		</ul>
	</div>

	<div class="mt-6 rounded-xl border border-slate-800 bg-slate-900/40 p-4">
		<h2 class="text-sm font-semibold text-slate-200">Next steps</h2>
		<ol class="mt-2 space-y-1.5 text-sm text-slate-400">
			<li><span class="font-mono text-xs text-slate-500">1.</span> Set <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-300">Cookie::$salt</code> in <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-300">application/bootstrap.php</code>.</li>
			<li><span class="font-mono text-xs text-slate-500">2.</span> Add your hosts to <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-300">application/config/url.php</code>.</li>
			<li><span class="font-mono text-xs text-slate-500">3.</span> Point your web server document root at <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-300">public/</code>.</li>
			<li><span class="font-mono text-xs text-slate-500">4.</span> Read <a class="text-ko8-300 underline underline-offset-2 hover:text-ko8-100" href="https://ko8.dev/guide/install">the installation guide</a>.</li>
		</ol>
	</div>

	<p class="mt-8 text-center font-mono text-xs text-slate-600">ko8 · environment check</p>

</main>
</body>
</html>
