<nav class="ko8-doc-menu text-sm" aria-label="API menu">

	<h2 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Packages</h2>

	<?php foreach ($menu as $package => $categories): ksort($categories); ?>
	<div class="mb-4">
		<h3 class="mb-1 font-mono text-xs font-semibold text-slate-700"><?php echo $package ?></h3>
		<?php foreach ($categories as $category => $classes): sort($classes); ?>
			<?php if ($category !== 'Base'): ?>
				<div class="mb-1 mt-2 text-[11px] uppercase tracking-wider text-slate-400"><?php echo $category ?></div>
			<?php endif ?>
			<ul class="space-y-0.5">
				<?php foreach ($classes as $class): ?>
				<li class="font-mono text-xs text-slate-600"><?php echo $class ?></li>
				<?php endforeach ?>
			</ul>
		<?php endforeach ?>
	</div>
	<?php endforeach ?>

</nav>
