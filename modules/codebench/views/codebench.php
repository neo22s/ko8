<?php
/**
 * ko8 codebench — benchmarking UI.
 *
 * @package    Ko8/Codebench
 * @category   Views
 */
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $class !== '' ? HTML::chars($class).' · ' : '' ?>Codebench · ko8</title>
<style><?php
$css = Kohana::find_file('media', 'css/ko8', 'css');
echo $css ? file_get_contents($css) : '';
?></style>
</head>
<body class="min-h-full bg-slate-950 text-slate-200 antialiased">

<header class="border-b border-slate-800 bg-slate-900/80">
	<div class="mx-auto flex max-w-5xl flex-wrap items-center gap-4 px-5 py-4">
		<span class="flex items-center gap-2 font-mono text-lg font-bold tracking-tight text-white">
			<span class="grid h-7 w-7 place-items-center rounded-md bg-ko8-600 text-sm text-white">k8</span>
			codebench
		</span>
		<span class="font-mono text-xs text-slate-500"><?php echo Kohana::VERSION ?></span>
		<button type="button" id="toggle_all"
			class="ml-auto rounded-md border border-slate-700 px-2.5 py-1.5 text-xs text-slate-300 hover:border-slate-500 hover:text-white">
			Expand all
		</button>
	</div>
</header>

<main class="mx-auto max-w-5xl px-5 py-8">

	<form id="runner" method="post" action="<?php echo URL::site('codebench') ?>" class="mb-8">
		<label for="bench-class" class="mb-1 block text-xs font-semibold uppercase tracking-wider text-slate-400">Benchmark class</label>
		<div class="flex flex-wrap gap-2">
			<input id="bench-class" name="class" type="text" value="<?php echo $class !== '' ? HTML::chars($class) : 'Bench_' ?>"
				class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 font-mono text-sm text-slate-100 outline-none placeholder:text-slate-500 focus:border-ko8-500 focus:ring-2 focus:ring-ko8-500/20">
			<button type="submit" class="rounded-lg bg-ko8-600 px-4 py-2 text-sm font-semibold text-white hover:bg-ko8-500">Run</button>
		</div>

		<?php if ( ! empty($class)): ?>
			<?php if (empty($codebench)): ?>
				<p class="mt-3 rounded-lg border border-rose-900 bg-rose-950/40 px-3 py-2 text-sm text-rose-300">Library not found.</p>
			<?php elseif (empty($codebench['benchmarks'])): ?>
				<p class="mt-3 rounded-lg border border-amber-900 bg-amber-950/40 px-3 py-2 text-sm text-amber-300">No methods found to benchmark. Prefix the methods with <code class="font-mono">bench</code>.</p>
			<?php endif ?>
		<?php endif ?>
	</form>

	<?php if ( ! empty($codebench) AND ! empty($codebench['benchmarks'])): ?>

	<ul id="bench" class="space-y-2">

		<?php foreach ($codebench['benchmarks'] as $method => $benchmark): ?>

		<?php
		$grade_colors = [
			'A' => 'bg-emerald-500',
			'B' => 'bg-lime-500',
			'C' => 'bg-yellow-500',
			'D' => 'bg-orange-500',
			'E' => 'bg-orange-600',
			'F' => 'bg-rose-600',
		];
		$grade = $benchmark['grade']['time'];
		$bar   = $grade_colors[$grade] ?? 'bg-slate-600';
		?>

		<li class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900/40">
			<button type="button" class="bench-toggle flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-slate-800/60"
				title="<?php printf('%01.6f', $benchmark['time']) ?>s">
				<span class="w-4 shrink-0 font-mono text-xs text-slate-500">▸</span>
				<span class="min-w-0 flex-1">
					<span class="flex items-center justify-between gap-3">
						<span class="truncate font-mono text-sm text-slate-100"><?php echo HTML::chars($method) ?></span>
						<span class="shrink-0 font-mono text-xs text-slate-500">+<?php echo (int) $benchmark['percent']['fastest']['time'] ?>%</span>
					</span>
					<span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-slate-800">
						<span class="block h-full rounded-full <?php echo $bar ?>" style="width:<?php echo (float) $benchmark['percent']['slowest']['time'] ?>%"></span>
					</span>
				</span>
			</button>

			<div class="bench-details hidden border-t border-slate-800">
				<div class="overflow-x-auto">
					<table class="w-full border-collapse text-left text-xs">
						<thead>
							<tr class="border-b border-slate-800 text-slate-500">
								<th class="px-4 py-2 font-normal">subject → return</th>
								<th class="px-4 py-2 text-right font-normal">Memory</th>
								<th class="px-4 py-2 text-right font-normal">Time</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-slate-800/70">
						<?php foreach ($benchmark['subjects'] as $subject_key => $subject): ?>
							<tr class="align-top hover:bg-slate-900/60">
								<td class="max-w-0 break-all px-4 py-2">
									<span class="font-mono text-slate-300" title="(<?php echo gettype($codebench['subjects'][$subject_key]) ?>) <?php echo HTML::chars(var_export($codebench['subjects'][$subject_key], TRUE)) ?>">
										[<?php echo HTML::chars($subject_key) ?>] →
									</span>
									<span class="text-slate-600">(<?php echo gettype($subject['return']) ?>)</span>
									<span class="font-mono text-slate-400"><?php echo HTML::chars(var_export($subject['return'], TRUE)) ?></span>
								</td>
								<td class="whitespace-nowrap px-4 py-2 text-right font-mono text-slate-400" title="+<?php echo (int) $subject['percent']['fastest']['memory'] ?>% memory">
									<?php echo Text::bytes($subject['memory'], 'MB', '%01.6f%s') ?>
								</td>
								<td class="whitespace-nowrap px-4 py-2 text-right font-mono text-slate-400" title="+<?php echo (int) $subject['percent']['fastest']['time'] ?>% time">
									<?php printf('%01.6f', $subject['time']) ?>s
								</td>
							</tr>
						<?php endforeach ?>
						</tbody>
					</table>
				</div>
			</div>
		</li>

		<?php endforeach ?>

	</ul>

	<?php if ( ! empty($codebench['description'])): ?>
	<div class="ko8-prose mt-8 max-w-none rounded-xl border border-slate-800 bg-slate-900/40 p-4 text-sm text-slate-400">
		<?php echo Text::auto_p(Text::auto_link($codebench['description']), FALSE) ?>
	</div>
	<?php endif ?>

	<?php endif ?>

	<footer class="mt-10 flex flex-wrap items-center justify-between gap-2 border-t border-slate-800 pt-4 font-mono text-xs text-slate-600">
		<span>
			<?php echo round(microtime(TRUE) - KOHANA_START_TIME, 2) ?>s
			· <?php echo Text::bytes(memory_get_usage(), 'MB') ?>
		</span>
		<span>ko8 <?php echo Kohana::VERSION ?></span>
	</footer>

</main>

<script>
(function () {
	var toggles = Array.prototype.slice.call(document.querySelectorAll('.bench-toggle'));
	var expand = document.getElementById('toggle_all');

	function detailsOf(button) {
		return button.parentNode.querySelector('.bench-details');
	}

	function arrowOf(button) {
		return button.querySelector('span');
	}

	function setOpen(button, open) {
		var details = detailsOf(button);

		if ( ! details) return;

		details.classList.toggle('hidden', ! open);
		arrowOf(button).textContent = open ? '▾' : '▸';
	}

	function allOpen() {
		return toggles.every(function (b) { return ! detailsOf(b).classList.contains('hidden'); });
	}

	toggles.forEach(function (button) {
		button.addEventListener('click', function () {
			setOpen(button, detailsOf(button).classList.contains('hidden'));

			expand.textContent = allOpen() ? 'Collapse all' : 'Expand all';
		});
	});

	expand.addEventListener('click', function () {
		var open = ! allOpen();

		toggles.forEach(function (b) { setOpen(b, open); });

		expand.textContent = open ? 'Collapse all' : 'Expand all';
	});

	<?php if (Kohana::$config->load('codebench')->expand_all): ?>
	toggles.forEach(function (b) { setOpen(b, true); });
	expand.textContent = 'Collapse all';
	<?php endif ?>

	var input = document.getElementById('bench-class');

	if (input) input.focus();
})();
</script>

</body>
</html>
