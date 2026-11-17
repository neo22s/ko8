<nav class="ko8-pagination flex flex-wrap items-center justify-center gap-1 py-4 text-sm" aria-label="Pagination">
	<?php if ($first_page !== FALSE): ?>
		<a class="rounded-md border border-slate-700 px-2.5 py-1.5 text-slate-300 no-underline hover:border-slate-500 hover:text-white" href="<?php echo HTML::chars($page->url($first_page)) ?>" rel="first"><?php echo __('First') ?></a>
	<?php else: ?>
		<span class="rounded-md border border-slate-800 px-2.5 py-1.5 text-slate-600"><?php echo __('First') ?></span>
	<?php endif ?>

	<?php if ($previous_page !== FALSE): ?>
		<a class="rounded-md border border-slate-700 px-2.5 py-1.5 text-slate-300 no-underline hover:border-slate-500 hover:text-white" href="<?php echo HTML::chars($page->url($previous_page)) ?>" rel="prev">&larr; <?php echo __('Previous') ?></a>
	<?php else: ?>
		<span class="rounded-md border border-slate-800 px-2.5 py-1.5 text-slate-600">&larr; <?php echo __('Previous') ?></span>
	<?php endif ?>

	<?php for ($i = 1; $i <= $total_pages; $i++): ?>
		<?php if ($i == $current_page): ?>
			<span class="rounded-md bg-ko8-600 px-3 py-1.5 font-semibold text-white" aria-current="page"><?php echo $i ?></span>
		<?php else: ?>
			<a class="rounded-md border border-transparent px-3 py-1.5 text-slate-400 no-underline hover:border-slate-700 hover:text-white" href="<?php echo HTML::chars($page->url($i)) ?>"><?php echo $i ?></a>
		<?php endif ?>
	<?php endfor ?>

	<?php if ($next_page !== FALSE): ?>
		<a class="rounded-md border border-slate-700 px-2.5 py-1.5 text-slate-300 no-underline hover:border-slate-500 hover:text-white" href="<?php echo HTML::chars($page->url($next_page)) ?>" rel="next"><?php echo __('Next') ?> &rarr;</a>
	<?php else: ?>
		<span class="rounded-md border border-slate-800 px-2.5 py-1.5 text-slate-600"><?php echo __('Next') ?> &rarr;</span>
	<?php endif ?>

	<?php if ($last_page !== FALSE): ?>
		<a class="rounded-md border border-slate-700 px-2.5 py-1.5 text-slate-300 no-underline hover:border-slate-500 hover:text-white" href="<?php echo HTML::chars($page->url($last_page)) ?>" rel="last"><?php echo __('Last') ?></a>
	<?php else: ?>
		<span class="rounded-md border border-slate-800 px-2.5 py-1.5 text-slate-600"><?php echo __('Last') ?></span>
	<?php endif ?>
</nav>
