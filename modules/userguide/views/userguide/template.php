<?php
/**
 * ko8 userguide · page shell.
 *
 * @package    Ko8/Userguide
 * @category   Views
 */
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo HTML::chars($title) ?> · ko8 docs</title>
<link rel="stylesheet" href="<?php echo Route::get('docs/media')->uri(['file' => 'css/ko8']) ?>">
</head>
<body class="min-h-full bg-white text-slate-700 antialiased">

<header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950">
	<div class="mx-auto flex max-w-7xl items-center gap-6 px-5 py-3">
		<a href="<?php echo Route::url('docs/guide') ?>" class="flex items-center gap-2 font-mono text-base font-bold tracking-tight text-white no-underline">
			<span class="grid h-7 w-7 place-items-center rounded-md bg-ko8-600 text-sm text-white">k8</span>
			ko8
		</a>

		<nav class="flex items-center gap-1 text-sm">
			<a class="rounded-md px-2.5 py-1.5 text-slate-300 no-underline hover:bg-slate-800 hover:text-white" href="<?php echo Route::url('docs/guide') ?>">User guide</a>
			<?php if (Kohana::$config->load('userguide.api_browser')): ?>
			<a class="rounded-md px-2.5 py-1.5 text-slate-300 no-underline hover:bg-slate-800 hover:text-white" href="<?php echo Route::url('docs/api') ?>">API</a>
			<?php endif ?>
		</nav>

		<span class="ml-auto hidden font-mono text-xs text-slate-500 sm:block">v<?php echo Kohana::VERSION ?></span>
	</div>
</header>

<div class="mx-auto flex max-w-7xl gap-8 px-5 py-8">

	<aside class="hidden w-56 shrink-0 lg:block">
		<nav class="ko8-doc-menu sticky top-20 max-h-[calc(100vh-6rem)] overflow-y-auto pr-2 text-sm" id="kodoc-topics">
			<?php echo $menu ?>
		</nav>
	</aside>

	<main class="min-w-0 flex-1">
		<?php if (count($breadcrumb) > 1): ?>
		<nav class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-slate-500" aria-label="Breadcrumb">
			<?php foreach ($breadcrumb as $link => $crumb): ?>
				<?php if (is_string($link)): ?>
					<a class="no-underline hover:text-ko8-600" href="<?php echo $link ?>"><?php echo $crumb ?></a>
					<span class="text-slate-300">/</span>
				<?php else: ?>
					<span class="text-slate-700"><?php echo $crumb ?></span>
				<?php endif ?>
			<?php endforeach ?>
		</nav>
		<?php endif ?>

		<article class="ko8-prose max-w-3xl" id="kodoc-body">
			<?php echo $content ?>
		</article>

		<?php if ( ! empty($show_comments)): ?>
		<div class="mt-12 border-t border-slate-200 pt-6">
			<p class="text-xs text-slate-400">Comments are disabled on this build. Use the issue tracker instead.</p>
		</div>
		<?php endif ?>
	</main>

</div>

<footer class="border-t border-slate-200 bg-slate-50">
	<div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-5 py-5 text-xs text-slate-500">
		<span><?php echo ! empty($copyright) ? $copyright : '&copy; ko8 contributors' ?></span>
		<span class="font-mono">ko8 <?php echo Kohana::VERSION ?> (<?php echo Kohana::CODENAME ?>)</span>
	</div>
</footer>

</body>
</html>
