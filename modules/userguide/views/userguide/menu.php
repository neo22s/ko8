<nav class="ko8-doc-menu text-sm" aria-label="Documentation menu">

	<h2 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Modules</h2>

	<?php if ( ! empty($modules)): ?>

		<ul class="space-y-1">
		<?php foreach ($modules as $url => $options): ?>
			<li>
				<?php echo HTML::anchor(Route::get('docs/guide')->uri(['module' => $url]), $options['name'], ['class' => 'block rounded-md px-2.5 py-1.5 text-slate-600 no-underline hover:bg-slate-100 hover:text-slate-900'], NULL, TRUE) ?>
			</li>
		<?php endforeach ?>
		</ul>

	<?php else: ?>

		<p class="text-slate-500">No modules.</p>

	<?php endif ?>

</nav>
