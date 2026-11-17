<div class="mt-4">
	<h4 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tags</h4>
	<ul class="flex flex-wrap gap-1.5">
	<?php foreach ($tags as $name => $set): ?>
		<li class="rounded-full border border-slate-200 px-2 py-0.5 font-mono text-[11px] text-slate-500">
			<?php echo ucfirst($name) ?><?php echo $set ? ' · '.implode(', ', $set) : '' ?>
		</li>
	<?php endforeach ?>
	</ul>
</div>
