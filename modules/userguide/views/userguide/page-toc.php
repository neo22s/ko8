<?php if (is_array($array)): ?>
<nav class="ko8-page-toc mb-8 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm" aria-label="On this page">
	<h2 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">On this page</h2>
	<ul class="space-y-1">
	<?php foreach ($array as $item): ?>
		<li class="<?php echo $item['level'] > 1 ? 'pl-3' : '' ?> <?php echo $item['level'] > 2 ? 'pl-6' : '' ?>">
			<a class="text-slate-600 no-underline hover:text-ko8-600" href="#<?php echo $item['id'] ?>"><?php echo $item['name'] ?></a>
		</li>
	<?php endforeach ?>
	</ul>
</nav>
<?php endif ?>
