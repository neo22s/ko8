<h1>User guide</h1>

<p>Every module can ship its own documentation pages. These are the modules that do:</p>

<?php if ( ! empty($modules)): ?>

	<?php foreach ($modules as $url => $options): ?>

		<div class="my-4 rounded-lg border border-slate-200 p-4">
			<h2 class="mt-0 mb-1 text-lg font-semibold">
				<?php echo HTML::anchor(Route::get('docs/guide')->uri(['module' => $url]), $options['name'], NULL, NULL, TRUE) ?>
			</h2>
			<p class="my-0 text-sm text-slate-500"><?php echo $options['description'] ?></p>
		</div>

	<?php endforeach ?>

<?php else: ?>

	<p class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
		No module with documentation pages was found.
	</p>

<?php endif ?>
