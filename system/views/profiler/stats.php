<?php
/**
 * ko8 profiler.
 *
 * Rendered at the end of the response when profiling is enabled. Self-contained
 * on purpose: it can run after the template has been flushed.
 *
 * @package    Ko8
 * @category   Views
 */
$group_stats      = Profiler::group_stats();
$group_cols       = ['min', 'max', 'average', 'total'];
$application_cols = ['min', 'max', 'average', 'current'];
$application      = Profiler::application();

/**
 * Format a duration with a human unit.
 */
$ko8_time = function ($seconds)
{
	if ($seconds < 0.001) return number_format($seconds * 1000000, 0).' µs';
	if ($seconds < 1)     return number_format($seconds * 1000, 2).' ms';

	return number_format($seconds, 3).' s';
};

/**
 * Format a memory amount with a human unit.
 */
$ko8_mem = function ($bytes)
{
	if ($bytes < 1024)    return number_format($bytes, 0).' B';
	if ($bytes < 1048576) return number_format($bytes / 1024, 1).' kB';

	return number_format($bytes / 1048576, 2).' MB';
};
?>
<div class="mt-10 border-t border-slate-800 bg-slate-950 pb-4 pt-6" id="ko8-profiler">
	<div class="mx-auto max-w-6xl px-5">

		<div class="mb-4 flex flex-wrap items-baseline gap-x-4 gap-y-1">
			<h2 class="font-mono text-sm font-semibold uppercase tracking-wider text-slate-400">Profiler</h2>
			<span class="font-mono text-xs text-slate-500">
				<?php echo $ko8_time($application['total']['time']) ?>
				· <?php echo $ko8_mem($application['total']['memory']) ?>
				· <?php echo $application['count'] ?> benchmarks
			</span>
		</div>

		<?php foreach (Profiler::groups() as $group => $benchmarks): ?>
		<div class="mb-5 overflow-hidden rounded-xl border border-slate-800">
			<div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-800 bg-slate-900 px-4 py-2">
				<h3 class="font-mono text-sm font-semibold text-slate-200"><?php echo HTML::chars(ucfirst($group)) ?></h3>
				<span class="font-mono text-xs text-slate-500">
					<?php echo $ko8_time($group_stats[$group]['total']['time']) ?>
					· <?php echo $ko8_mem($group_stats[$group]['total']['memory']) ?>
				</span>
			</div>
			<div class="overflow-x-auto">
				<table class="w-full border-collapse text-left text-xs">
					<thead>
						<tr class="border-b border-slate-800 text-slate-500">
							<th class="py-1.5 pl-4 pr-3 font-normal">Benchmark</th>
							<?php foreach ($group_cols as $key): ?>
							<th class="py-1.5 pr-3 text-right font-normal"><?php echo ucfirst($key) ?></th>
							<?php endforeach ?>
						</tr>
					</thead>
					<tbody class="divide-y divide-slate-800/70">
					<?php foreach ($benchmarks as $name => $tokens): ?>
						<?php $stats = Profiler::stats($tokens) ?>
						<tr class="align-top odd:bg-slate-900/20">
							<th class="max-w-0 break-all py-2 pl-4 pr-3 text-left font-normal text-slate-300" scope="row">
								<?php echo HTML::chars($name) ?>
								<span class="ml-1 font-mono text-[10px] text-slate-600">×<?php echo count($tokens) ?></span>
							</th>
							<?php foreach ($group_cols as $key): ?>
							<td class="py-2 pr-3 text-right font-mono text-slate-400">
								<?php echo $ko8_time($stats[$key]['time']) ?>
								<span class="block text-[10px] text-slate-600"><?php echo $ko8_mem($stats[$key]['memory']) ?></span>
							</td>
							<?php endforeach ?>
						</tr>
					<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php endforeach ?>

		<div class="overflow-hidden rounded-xl border border-ko8-700/60">
			<div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-ko8-700/60 bg-ko8-950 px-4 py-2">
				<h3 class="font-mono text-sm font-semibold text-ko8-100">Application execution</h3>
				<span class="font-mono text-xs text-ko8-300">
					<?php echo $ko8_time($application['total']['time']) ?>
					· <?php echo $ko8_mem($application['total']['memory']) ?>
				</span>
			</div>
			<div class="grid grid-cols-2 divide-x divide-slate-800 sm:grid-cols-4">
			<?php foreach ($application_cols as $key): ?>
				<div class="px-4 py-2.5">
					<div class="text-[10px] uppercase tracking-wider text-slate-500"><?php echo $key ?></div>
					<div class="mt-0.5 font-mono text-sm text-slate-200"><?php echo $ko8_time($application[$key]['time']) ?></div>
					<div class="font-mono text-[10px] text-slate-600"><?php echo $ko8_mem($application[$key]['memory']) ?></div>
				</div>
			<?php endforeach ?>
			</div>
		</div>

	</div>
</div>
