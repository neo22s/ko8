<div class="mb-8 border-b border-slate-200 pb-4">
	<h1 class="mt-0 mb-1 break-words">
		<span class="font-normal text-slate-400"><?php echo $doc->modifiers ?></span><?php echo $doc->class->name ?>
	</h1>
	<?php foreach ($doc->parents as $parent): ?>
		<p class="my-0 text-sm text-slate-500">extends <?php echo Kodoc::class_link($parent->name) ?></p>
	<?php endforeach ?>
	<?php if ($interfaces = $doc->class->getInterfaceNames()): ?>
		<p class="my-0 text-sm text-slate-500">implements
			<?php foreach ($interfaces as $i => $interface): ?>
				<?php echo $i ? ', ' : '' ?><?php echo Kodoc::class_link($interface) ?>
			<?php endforeach ?>
		</p>
	<?php endif ?>
</div>

<?php if ($child = $doc->is_transparent($doc->class->name)): ?>
<div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
	This class is a transparent base class for <?php echo Kodoc::class_link($child) ?> and should not be accessed directly.
</div>
<?php endif ?>

<div class="ko8-prose max-w-none">
	<?php echo $doc->description() ?>
</div>

<?php if ($doc->tags): ?>
<div class="my-6 flex flex-wrap gap-1.5">
	<?php foreach ($doc->tags() as $name => $set): ?>
		<?php foreach ($set as $tag): ?>
		<span class="rounded-full border border-slate-200 px-2 py-0.5 font-mono text-[11px] text-slate-500"><?php echo $name ?>: <?php echo $tag ?></span>
		<?php endforeach ?>
	<?php endforeach ?>
</div>
<?php endif ?>

<p class="my-6 font-mono text-xs text-slate-500">
	<?php if ($path = $doc->class->getFilename()): ?>
		Declared in <span class="text-slate-700"><?php echo Debug::path($path) ?></span> on line <?php echo $doc->class->getStartLine() ?>.
	<?php else: ?>
		Declared internally in PHP. <a class="text-ko8-600 hover:underline" href="https://www.php.net/manual/class.<?php echo strtolower($doc->class->name) ?>.php">PHP manual</a>
	<?php endif ?>
</p>

<nav class="mb-8 grid gap-3 sm:grid-cols-3">
	<div class="rounded-lg border border-slate-200 p-3">
		<h3 class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Constants</h3>
		<?php if ($doc->constants): ?>
			<ul class="space-y-0.5 font-mono text-xs">
			<?php foreach ($doc->constants as $name => $value): ?>
				<li><a class="text-slate-600 no-underline hover:text-ko8-600" href="#constant:<?php echo $name ?>"><?php echo $name ?></a></li>
			<?php endforeach ?>
			</ul>
		<?php else: ?>
			<p class="my-0 text-xs text-slate-400">None</p>
		<?php endif ?>
	</div>
	<div class="rounded-lg border border-slate-200 p-3">
		<h3 class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Properties</h3>
		<?php if ($properties = $doc->properties()): ?>
			<ul class="space-y-0.5 font-mono text-xs">
			<?php foreach ($properties as $prop): ?>
				<li><a class="text-slate-600 no-underline hover:text-ko8-600" href="#property:<?php echo $prop->property->name ?>">$<?php echo $prop->property->name ?></a></li>
			<?php endforeach ?>
			</ul>
		<?php else: ?>
			<p class="my-0 text-xs text-slate-400">None</p>
		<?php endif ?>
	</div>
	<div class="rounded-lg border border-slate-200 p-3">
		<h3 class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Methods</h3>
		<?php if ($methods = $doc->methods()): ?>
			<ul class="space-y-0.5 font-mono text-xs">
			<?php foreach ($methods as $method): ?>
				<li><a class="text-slate-600 no-underline hover:text-ko8-600" href="#<?php echo $method->method->name ?>"><?php echo $method->method->name ?>()</a></li>
			<?php endforeach ?>
			</ul>
		<?php else: ?>
			<p class="my-0 text-xs text-slate-400">None</p>
		<?php endif ?>
	</div>
</nav>

<?php if ($doc->constants): ?>
<section class="mb-8">
	<h2 id="constants" class="mb-3 border-b border-slate-200 pb-2 text-xl font-semibold text-slate-900">Constants</h2>
	<dl class="space-y-3">
	<?php foreach ($doc->constants() as $name => $value): ?>
		<div>
			<dt><h4 id="constant:<?php echo $name ?>" class="my-0 font-mono text-sm font-semibold text-slate-800"><?php echo $name ?></h4></dt>
			<dd class="my-1 font-mono text-xs text-slate-600"><?php echo $value ?></dd>
		</div>
	<?php endforeach ?>
	</dl>
</section>
<?php endif ?>

<?php if ($properties = $doc->properties()): ?>
<section class="mb-8">
	<h2 id="properties" class="mb-3 border-b border-slate-200 pb-2 text-xl font-semibold text-slate-900">Properties</h2>
	<div class="space-y-4">
	<?php foreach ($properties as $prop): ?>
		<div class="rounded-lg border border-slate-200 p-4">
			<h4 id="property:<?php echo $prop->property->name ?>" class="my-0 font-mono text-sm text-slate-800">
				<span class="text-slate-400"><?php echo $prop->modifiers ?></span>
				<?php if ($prop->type): ?><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs"><?php echo $prop->type ?></code><?php endif ?>
				$<?php echo $prop->property->name ?>
			</h4>
			<?php if ($prop->description): ?><p class="my-2 text-sm text-slate-600"><?php echo $prop->description ?></p><?php endif ?>
			<?php if ($prop->value): ?><p class="my-1 font-mono text-xs text-slate-500"><?php echo $prop->value ?></p><?php endif ?>
			<?php if ($prop->default !== $prop->value): ?>
				<p class="my-1 text-xs text-slate-400">default <span class="font-mono"><?php echo $prop->default ?></span></p>
			<?php endif ?>
		</div>
	<?php endforeach ?>
	</div>
</section>
<?php endif ?>

<?php if ($methods = $doc->methods()): ?>
<section>
	<h2 id="methods" class="mb-3 border-b border-slate-200 pb-2 text-xl font-semibold text-slate-900">Methods</h2>
	<div class="space-y-4">
	<?php foreach ($methods as $method): ?>
		<?php echo View::factory('userguide/api/method')->set('doc', $method)->set('route', $route) ?>
	<?php endforeach ?>
	</div>
</section>
<?php endif ?>
