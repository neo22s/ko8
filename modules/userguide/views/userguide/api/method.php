<?php
/** @var Kohana_Kodoc_Method $doc */
$declares = $doc->method->getDeclaringClass();
?>
<div class="rounded-lg border border-slate-200">
	<div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-2.5">
		<h3 id="<?php echo $doc->method->name ?>" class="my-0 break-all font-mono text-sm text-slate-900">
			<span class="text-slate-400"><?php echo $doc->modifiers ?></span><?php echo $doc->method->name ?>(<?php echo $doc->params ? $doc->params_short() : '' ?>)
		</h3>
		<?php if ($declares->name !== $doc->class->name): ?>
		<span class="font-mono text-[11px] text-slate-500">
			inherited from <a class="text-ko8-600 no-underline hover:underline" href="<?php echo $route->uri(['class' => $declares->name]) ?>"><?php echo $declares->name ?></a>
		</span>
		<?php endif ?>
	</div>

	<div class="px-4 py-3">
		<?php if ($doc->description): ?>
		<div class="ko8-prose max-w-none text-sm"><?php echo $doc->description ?></div>
		<?php endif ?>

		<?php if ($doc->params): ?>
		<h4 class="mb-2 mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Parameters</h4>
		<ul class="space-y-1.5">
		<?php foreach ($doc->params as $param): ?>
			<li class="flex flex-wrap items-baseline gap-2 text-sm">
				<?php if ($param->type): ?><code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700"><?php echo $param->type ?></code><?php endif ?>
				<strong class="font-mono text-xs text-slate-800"><?php echo $param->reference ? '&amp;' : '' ?>$<?php echo $param->name ?></strong>
				<span class="font-mono text-[11px] text-slate-400"><?php echo $param->default ? '= '.$param->default : 'required' ?></span>
				<?php if ($param->description): ?><span class="text-xs text-slate-500"><?php echo $param->description ?></span><?php endif ?>
			</li>
		<?php endforeach ?>
		</ul>
		<?php endif ?>

		<?php if ($doc->tags) echo View::factory('userguide/api/tags')->set('tags', $doc->tags) ?>

		<?php if ($doc->return): ?>
		<h4 class="mb-2 mt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Returns</h4>
		<ul class="space-y-1">
		<?php foreach ($doc->return as $set): list($type, $text) = $set; ?>
			<li class="text-sm">
				<code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700"><?php echo HTML::chars($type) ?></code>
				<?php if ($text): ?><span class="text-xs text-slate-500"><?php echo HTML::chars(ucfirst($text)) ?></span><?php endif ?>
			</li>
		<?php endforeach ?>
		</ul>
		<?php endif ?>

		<?php if ($doc->source): ?>
		<details class="mt-4">
			<summary class="cursor-pointer text-[11px] font-semibold uppercase tracking-wider text-slate-400 hover:text-slate-600">Source</summary>
			<pre class="mt-2 overflow-x-auto rounded-lg bg-slate-950 p-4 font-mono text-xs leading-relaxed text-slate-200"><code><?php echo HTML::chars($doc->source) ?></code></pre>
		</details>
		<?php endif ?>
	</div>
</div>
