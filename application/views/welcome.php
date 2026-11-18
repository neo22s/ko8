<?php
/**
 * ko8 welcome page.
 *
 * The default route lands here. It is deliberately static and dependency-free:
 * if this page renders, the framework, the cascade and the view layer all work.
 *
 * @package    Ko8
 * @category   Views
 */
$css = '';
if ($file_css = Kohana::find_file('media', 'css/ko8', 'css'))
{
	$css = file_get_contents($file_css);
}

$checks = [
	'PHP'        => PHP_VERSION,
	'extensions' => implode(' · ', array_slice(get_loaded_extensions(), 0, 6)).'…',
	'views'      => Kohana::find_file('views', 'welcome') ? 'cascade ok' : 'missing',
];
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ko8 <?php echo Kohana::VERSION ?></title>
<style><?php echo $css ?></style>
</head>
<body class="min-h-full bg-slate-950 text-slate-200 antialiased">

<header class="border-b border-slate-800 bg-slate-900/70">
	<div class="mx-auto flex max-w-4xl items-center gap-4 px-5 py-4">
		<span class="flex items-center gap-2 font-mono text-lg font-bold tracking-tight text-white">
			<span class="grid h-7 w-7 place-items-center rounded-md bg-ko8-600 text-sm text-white">k8</span>
			ko8
		</span>
		<span class="rounded-full border border-slate-700 px-2.5 py-0.5 font-mono text-xs text-slate-400">v<?php echo Kohana::VERSION ?></span>
		<span class="ml-auto font-mono text-xs text-slate-500"><?php echo Kohana::CODENAME ?></span>
	</div>
</header>

<main class="mx-auto max-w-4xl px-5 py-12">

	<h1 class="text-3xl font-bold tracking-tight text-white">It works.</h1>
	<p class="mt-3 max-w-2xl text-slate-400">
		This is the default page of a ko8 application: the Koseven / Kohana 3.3 API running on
		modern PHP. Edit <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-200">application/classes/Controller/Welcome.php</code>
		to make it yours.
	</p>

	<div class="mt-8 grid gap-3 sm:grid-cols-3">
		<?php foreach ($checks as $label => $value): ?>
		<div class="rounded-xl border border-slate-800 bg-slate-900/40 px-4 py-3">
			<div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500"><?php echo $label ?></div>
			<div class="mt-1 truncate font-mono text-xs text-slate-300"><?php echo HTML::chars($value) ?></div>
		</div>
		<?php endforeach ?>
	</div>

	<h2 class="mt-12 text-sm font-semibold uppercase tracking-wider text-slate-400">Next steps</h2>
	<ol class="mt-3 space-y-2 text-sm text-slate-400">
		<li><span class="font-mono text-xs text-slate-600">1.</span> Set <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-200">Cookie::$salt</code> in <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-200">application/bootstrap.php</code>.</li>
		<li><span class="font-mono text-xs text-slate-600">2.</span> Add your hosts to <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-200">application/config/url.php</code>.</li>
		<li><span class="font-mono text-xs text-slate-600">3.</span> Enable the modules you need in <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-200">Kohana::modules()</code>.</li>
		<li><span class="font-mono text-xs text-slate-600">4.</span> Delete <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs text-slate-200">public/install.php</code>.</li>
	</ol>

	<?php if (Kohana::find_file('views', 'userguide/index')): ?>
	<p class="mt-10 flex flex-wrap gap-2">
		<a class="rounded-lg bg-ko8-600 px-3.5 py-2 text-sm font-semibold text-white no-underline hover:bg-ko8-500" href="<?php echo URL::site('guide') ?>">Read the docs</a>
		<a class="rounded-lg border border-slate-700 px-3.5 py-2 text-sm font-semibold text-slate-300 no-underline hover:border-slate-500 hover:text-white" href="<?php echo URL::site('guide/kohana/upgrading-from-kohana') ?>">Migrating to ko8</a>
	</p>
	<?php endif ?>

</main>

<footer class="border-t border-slate-800">
	<div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-2 px-5 py-5 font-mono text-xs text-slate-600">
		<span><?php echo Kohana::version() ?></span>
		<span>Koseven API <?php echo Kohana::KOSEVEN_COMPAT ?></span>
	</div>
</footer>

</body>
</html>
