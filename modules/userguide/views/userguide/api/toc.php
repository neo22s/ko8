<h1>Available classes</h1>

<p class="text-sm text-slate-500">The API browser reads the docblocks of every loaded class, including your application's.</p>

<div class="my-6">
	<label for="kodoc-api-filter-box" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-500">Filter</label>
	<input type="text" id="kodoc-api-filter-box" placeholder="Arr, Request, ORM…"
		class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm outline-none placeholder:text-slate-400 focus:border-ko8-500 focus:ring-2 focus:ring-ko8-500/20">
</div>

<?php
// class_methods() returns array(class => array(method, ...)).
$names = array_keys($classes);
sort($names);
?>

<div id="kodoc-api-classes" class="flex flex-wrap gap-2">
<?php foreach ($names as $name): ?>
	<a class="kodoc-api-class inline-block rounded-md border border-slate-200 px-2.5 py-1 font-mono text-xs text-slate-700 no-underline hover:border-ko8-300 hover:bg-ko8-50"
		href="<?php echo Route::url('docs/api', ['class' => $name]) ?>"><?php echo HTML::chars($name) ?></a>
<?php endforeach ?>
</div>

<p id="kodoc-api-empty" class="hidden rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No class matches that filter.</p>

<script>
(function () {
	var box = document.getElementById('kodoc-api-filter-box');
	var items = Array.prototype.slice.call(document.querySelectorAll('.kodoc-api-class'));
	var empty = document.getElementById('kodoc-api-empty');

	box.addEventListener('input', function () {
		var needle = box.value.trim().toLowerCase();
		var shown = 0;

		items.forEach(function (item) {
			var match = item.textContent.toLowerCase().indexOf(needle) !== -1;
			item.classList.toggle('hidden', ! match);
			if (match) shown++;
		});

		empty.classList.toggle('hidden', shown !== 0);
	});
})();
</script>
