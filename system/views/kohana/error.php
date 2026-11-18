<?php
/**
 * ko8 error page.
 *
 * Rendered when an uncaught exception reaches the end of the request. It may
 * run before the framework is fully initialized, so nothing here may depend on
 * I18n, the request stack or the asset pipeline.
 *
 * @package    Ko8
 * @category   Views
 */
if ( ! function_exists('__'))
{
	// Kohana_Exception can be triggered before I18n is loaded.
	function __($message, $variables = NULL)
	{
		return is_array($variables) ? strtr($message, $variables) : $message;
	}
}

// Unique error identifier
$error_id = uniqid('error');

// Inline the stylesheet: this page has to render even when the web server
// cannot serve a static asset (bad document root, broken rewrite rules...).
$css = '';
if (class_exists('Kohana') AND $file_css = Kohana::find_file('media', 'css/ko8', 'css'))
{
	$css = file_get_contents($file_css);
}

$is_http = ($class instanceof HTTP_Exception);

?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo HTML::chars($class) ?> · ko8</title>
<style><?php echo $css ?></style>
</head>
<body class="min-h-full bg-slate-950 text-slate-200 antialiased" id="kohana_error">

<header class="border-b border-slate-800 bg-slate-900/80">
	<div class="mx-auto flex max-w-6xl items-center gap-4 px-5 py-4">
		<span class="flex items-center gap-2 font-mono text-lg font-bold tracking-tight text-white">
			<span class="grid h-7 w-7 place-items-center rounded-md bg-ko8-600 text-sm text-white">k8</span>
			ko8
		</span>
		<span class="rounded-full border border-slate-700 px-2.5 py-0.5 font-mono text-xs text-slate-400">
			v<?php echo Kohana::VERSION ?>
		</span>
		<span class="ml-auto font-mono text-xs text-slate-500">error <?php echo HTML::chars((string) $error_id) ?></span>
	</div>
</header>

<main class="mx-auto max-w-6xl px-5 py-8">

	<div class="rounded-xl border <?php echo $is_http ? 'border-sky-900 bg-sky-950/40' : 'border-rose-900 bg-rose-950/40' ?> p-5">
		<div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
			<span class="rounded-md bg-slate-900 px-2 py-0.5 font-mono text-xs font-semibold uppercase tracking-wider text-slate-300"><?php echo HTML::chars($class) ?></span>
			<span class="font-mono text-sm text-slate-400">[ <?php echo HTML::chars((string) $code) ?> ]</span>
		</div>
		<h1 class="mt-3 break-words font-mono text-xl leading-snug text-white"><?php echo HTML::chars(strip_tags((string) $message)) ?></h1>
		<p class="mt-3 font-mono text-sm text-slate-400">
			<?php echo HTML::chars(Debug::path($file)) ?>
			<span class="text-slate-600">line</span>
			<span class="text-slate-300"><?php echo (int) $line ?></span>
		</p>
	</div>

	<div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
		<div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-4 py-2.5">
			<h2 class="text-sm font-semibold text-slate-200">Source</h2>
			<span class="font-mono text-xs text-slate-500"><?php echo basename($file) ?></span>
		</div>
		<div class="overflow-x-auto bg-slate-950"><?php echo Debug::source($file, $line) ?></div>
	</div>

	<div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
		<div class="border-b border-slate-800 bg-slate-900 px-4 py-2.5">
			<h2 class="text-sm font-semibold text-slate-200">Stack trace</h2>
		</div>
		<ol class="divide-y divide-slate-800">
		<?php foreach (Debug::trace($trace) as $i => $step): ?>
			<li class="bg-slate-950/60">
				<details class="group">
					<summary class="flex cursor-pointer list-none items-start gap-3 px-4 py-2.5 hover:bg-slate-900/70">
						<span class="mt-0.5 w-7 shrink-0 text-right font-mono text-xs text-slate-600"><?php echo $i ?></span>
						<span class="min-w-0 flex-1">
							<span class="block break-all font-mono text-sm text-slate-200"><?php echo HTML::chars($step['function']) ?>()</span>
							<?php if ($step['file']): ?>
								<span class="mt-1 block break-all font-mono text-xs text-slate-500"><?php echo HTML::chars(Debug::path($step['file'])) ?><span class="text-slate-700">:</span><?php echo (int) $step['line'] ?></span>
							<?php else: ?>
								<span class="mt-1 block font-mono text-xs text-slate-600">{PHP internal call}</span>
							<?php endif ?>
						</span>
						<?php if ($step['args']): ?>
							<span class="mt-0.5 shrink-0 font-mono text-xs text-slate-500 group-open:text-ko8-300"><?php echo count($step['args']) ?> arg<?php echo count($step['args']) === 1 ? '' : 's' ?></span>
						<?php endif ?>
					</summary>

					<?php if ($step['args']): ?>
					<div class="border-t border-slate-800/70 bg-slate-900/40 px-4 py-3">
						<table class="w-full border-collapse text-left">
						<?php foreach ($step['args'] as $name => $arg): ?>
							<tr class="align-top">
								<td class="w-1/4 py-1 pr-3 font-mono text-xs text-ko8-300"><?php echo HTML::chars((string) $name) ?></td>
								<td class="py-1"><pre class="overflow-x-auto whitespace-pre-wrap break-all font-mono text-xs text-slate-300"><code><?php echo Debug::dump($arg) ?></code></pre></td>
							</tr>
						<?php endforeach ?>
						</table>
					</div>
					<?php endif ?>

					<?php if ($step['source']): ?>
					<div class="border-t border-slate-800/70 bg-slate-950 px-4 py-3"><?php echo $step['source'] ?></div>
					<?php endif ?>
				</details>
			</li>
		<?php endforeach ?>
		</ol>
	</div>

	<div class="mt-6 overflow-hidden rounded-xl border border-slate-800">
		<details>
			<summary class="flex cursor-pointer list-none items-center justify-between bg-slate-900 px-4 py-2.5 hover:bg-slate-800/80">
				<h2 class="text-sm font-semibold text-slate-200">Environment</h2>
				<span class="font-mono text-xs text-slate-500"><?php echo PHP_VERSION ?> · <?php echo PHP_SAPI ?></span>
			</summary>

			<div class="divide-y divide-slate-800 bg-slate-950/60">

				<?php $included = get_included_files() ?>
				<details>
					<summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2 hover:bg-slate-900/60">
						<h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Included files</h3>
						<span class="font-mono text-xs text-slate-500"><?php echo count($included) ?></span>
					</summary>
					<ul class="max-h-80 overflow-auto px-4 pb-3">
						<?php foreach ($included as $included_file): ?>
						<li class="break-all py-0.5 font-mono text-xs text-slate-400"><?php echo HTML::chars(Debug::path($included_file)) ?></li>
						<?php endforeach ?>
					</ul>
				</details>

				<?php $extensions = get_loaded_extensions() ?>
				<details>
					<summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2 hover:bg-slate-900/60">
						<h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400">Loaded extensions</h3>
						<span class="font-mono text-xs text-slate-500"><?php echo count($extensions) ?></span>
					</summary>
					<ul class="flex flex-wrap gap-1.5 px-4 pb-3">
						<?php foreach ($extensions as $extension): ?>
						<li class="rounded border border-slate-800 bg-slate-900 px-1.5 py-0.5 font-mono text-xs text-slate-400"><?php echo HTML::chars($extension) ?></li>
						<?php endforeach ?>
					</ul>
				</details>

				<?php foreach (['_SESSION', '_GET', '_POST', '_FILES', '_COOKIE', '_SERVER'] as $var): ?>
					<?php if (empty($GLOBALS[$var]) OR ! is_array($GLOBALS[$var])) continue ?>
					<details>
						<summary class="flex cursor-pointer list-none items-center justify-between px-4 py-2 hover:bg-slate-900/60">
							<h3 class="font-mono text-xs font-semibold text-slate-400">$<?php echo $var ?></h3>
							<span class="font-mono text-xs text-slate-500"><?php echo count($GLOBALS[$var]) ?></span>
						</summary>
						<div class="max-h-80 overflow-auto px-4 pb-3">
							<table class="w-full border-collapse text-left">
							<?php foreach ($GLOBALS[$var] as $key => $value): ?>
								<tr class="align-top border-t border-slate-800/60">
									<td class="w-1/4 py-1 pr-3 font-mono text-xs text-ko8-300"><?php echo HTML::chars((string) $key) ?></td>
									<td class="py-1"><pre class="overflow-x-auto whitespace-pre-wrap break-all font-mono text-xs text-slate-300"><code><?php echo Debug::dump($value) ?></code></pre></td>
								</tr>
							<?php endforeach ?>
							</table>
						</div>
					</details>
				<?php endforeach ?>

			</div>
		</details>
	</div>

	<p class="mt-8 text-center font-mono text-xs text-slate-600">
		ko8 <?php echo Kohana::VERSION ?> (<?php echo Kohana::CODENAME ?>)
		· <span>guide/kohana/errors</span>
	</p>

</main>
</body>
</html>
