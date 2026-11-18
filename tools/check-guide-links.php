<?php
/**
 * Find guide links that resolve to a page that does not exist.
 *
 * It resolves links with URL semantics, the same way the browser does, because
 * that is where the bugs are: a docblock link is rendered with /guide/ as its
 * base URL, and a Markdown link is relative to /guide/<module>/<page>.
 *
 * Images are skipped: they are served by the guide-media route, not by a guide
 * page.
 *
 * Uso: php tools/check-guide-links.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);

// Bootstrap the framework: `api/ClassName` links are only valid if the class is
// actually loadable, and that depends on the modules being enabled.
define('EXT', '.php');
define('DOCROOT', $root.'/');
define('SYSPATH', DOCROOT.'system/');
define('APPPATH', DOCROOT.'application/');
define('MODPATH', DOCROOT.'modules/');
define('KOHANA_START_TIME', microtime(TRUE));
define('KOHANA_START_MEMORY', memory_get_usage());

require DOCROOT.'vendor/autoload.php';
require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

spl_autoload_register(['Kohana', 'auto_load']);

Kohana::init(['base_url' => '/', 'errors' => FALSE, 'profile' => FALSE, 'caching' => FALSE]);
Kohana::$config->attach(new Config_File);

$modules = [];

foreach (glob(MODPATH.'*', GLOB_ONLYDIR) AS $dir)
{
	$modules[basename($dir)] = $dir;
}

Kohana::modules($modules);

/**
 * Every guide page that exists, keyed as "module/page".
 *
 * @return array<string,string>
 */
function guide_pages(string $root): array
{
	$pages = [];

	$roots = [];

	// The core guide is served as the "kohana" module.
	foreach (glob($root.'/system/guide/*', GLOB_ONLYDIR) AS $dir)
	{
		$roots[basename($dir)] = $dir;
	}

	foreach (glob($root.'/modules/*/guide/*', GLOB_ONLYDIR) AS $dir)
	{
		$roots[basename($dir)] = $dir;
	}

	foreach ($roots AS $module => $dir)
	{
		if ($module === 'de-de')
		{
			continue;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator AS $file)
		{
			if (substr($file->getFilename(), -3) !== '.md')
			{
				continue;
			}

			$page = substr($file->getPathname(), strlen($dir) + 1, -3);

			$pages[$module.'/'.$page] = $file->getPathname();
		}
	}

	return $pages;
}

/**
 * Resolve "." and ".." segments the way a browser does.
 */
function resolve_path(string $path): string
{
	$segments = [];

	foreach (explode('/', $path) AS $segment)
	{
		if ($segment === '' OR $segment === '.')
		{
			continue;
		}

		if ($segment === '..')
		{
			array_pop($segments);

			continue;
		}

		$segments[] = $segment;
	}

	return implode('/', $segments);
}

/**
 * Is this link an asset rather than a page?
 */
function is_asset(string $target): bool
{
	return (bool) preg_match('/\.(png|jpe?g|gif|svg|webp|ico|css|js|zip|gz|txt)$/i', $target);
}

/**
 * Check a link found on a page whose URL is /guide/<module>[/<page>]. A docblock
 * is rendered with /guide/ as base, so its page is the empty string.
 *
 * @param array<string,string> $pages
 * @param array<string,array<string>> $problems
 */
function check(array &$problems, array $pages, string $module, string $page, string $target, string $origin): void
{
	$target = trim($target);

	if ($target === '' OR str_starts_with($target, '#') OR str_starts_with($target, 'mailto:'))
	{
		return;
	}

	// External, protocol-relative, and asset links are not guide pages.
	if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $target) OR str_starts_with($target, '//') OR is_asset($target))
	{
		return;
	}

	$target = preg_replace('/[#?].*$/', '', $target);

	// `../api/Class` and `api/Class` are the same thing outside a module page.
	$api_target = preg_replace('~^(?:\.\./)+api/~', 'api/', $target);

	if (str_starts_with($api_target, 'api/'))
	{
		$class = substr($api_target, 4);

		if ( ! class_exists($class) AND ! interface_exists($class) AND ! trait_exists($class))
		{
			$problems[$api_target][] = $origin;
		}

		return;
	}

	// An absolute path is absolute to the site root.
	if (str_starts_with($target, '/'))
	{
		$resolved = ltrim($target, '/');
	}
	else
	{
		// Links are relative to the module root, which is the convention of the
		// whole guide (see Kohana_Kodoc_Links::resolve()).
		$resolved = resolve_path($module.'/'.$target);
	}

	// A page may also be addressed as its directory.
	if (isset($pages[$resolved]) OR isset($pages[$resolved.'/index']))
	{
		return;
	}

	$problems[$resolved][] = $origin;
}

/**
 * Remove code from Markdown: fenced blocks and inline spans. Links inside code
 * are examples, not links.
 */
function strip_markdown_code(string $text): string
{
	$text = preg_replace('/^```.*?^```/ms', '', $text);
	$text = preg_replace('/^(?:\t| {4,}).*$/m', '', $text);
	$text = preg_replace('/`[^`\n]*`/', '', $text);

	return $text;
}

/**
 * Remove indented code blocks from a docblock.
 */
function strip_docblock_code(string $doc): string
{
	$lines = [];

	foreach (explode("\n", $doc) AS $line)
	{
		if (preg_match('/^\s*\*\s{4,}\S/', $line))
		{
			continue;
		}

		$lines[] = $line;
	}

	return implode("\n", $lines);
}

$pages = guide_pages($root);

echo 'guide pages: '.count($pages)."\n\n";

$problems = [];

// 1. Guide Markdown: relative to /guide/<module>/<page>.
$roots = [];

foreach (glob($root.'/system/guide/*', GLOB_ONLYDIR) AS $dir)
{
	$roots[basename($dir)] = $dir;
}

foreach (glob($root.'/modules/*/guide/*', GLOB_ONLYDIR) AS $dir)
{
	$roots[basename($dir)] = $dir;
}

foreach ($roots AS $module => $dir)
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator AS $file)
	{
		if (substr($file->getFilename(), -3) !== '.md')
		{
			continue;
		}

		$page    = substr($file->getPathname(), strlen($dir) + 1, -3);
		$content = strip_markdown_code(file_get_contents($file->getPathname()));
		$origin  = str_replace($root.'/', '', $file->getPathname());

		// de-de is a partial translation of 2012 with links that were never
		// ported; it is not part of the current guide.
		if ($module === 'de-de')
		{
			continue;
		}

		if (preg_match_all('/\]\(([^)\s]+)/', $content, $matches))
		{
			foreach ($matches[1] AS $target)
			{
				check($problems, $pages, $module, $page, $target, $origin);
			}
		}
	}
}

// 2. Docblocks: rendered in the API browser with /guide/ as base URL, so a link
//    "kohana/conventions" means the "kohana" module.
$class_dirs = [$root.'/system/classes'];

foreach (glob($root.'/modules/*/classes', GLOB_ONLYDIR) AS $dir)
{
	$class_dirs[] = $dir;
}

foreach ($class_dirs AS $dir)
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator AS $file)
		{
		if (substr($file->getFilename(), -4) !== '.php')
		{
			continue;
		}

		// codebench holds Markdown *fixtures* for a parser benchmark, not docs.
		if (str_contains($file->getPathname(), '/codebench/'))
		{
			continue;
		}

		// The resolver documents its own syntax with example links; they are
		// illustrative, not real.
		if (str_contains($file->getPathname(), '/Kodoc/Links.php'))
		{
			continue;
		}

		$content = strip_docblock_code(file_get_contents($file->getPathname()));
		$origin  = str_replace($root.'/', '', $file->getPathname());

		if (preg_match_all('/\]\(([^)\s]+)/', $content, $matches))
		{
			foreach ($matches[1] AS $target)
			{
				// Docblocks are rendered with the guide root as base, so a link
				// there is "module/page" or "api/Class".
				check($problems, $pages, '', '', $target, $origin);
			}
		}
	}
}

ksort($problems);

echo 'broken targets: '.count($problems)."\n\n";

foreach ($problems AS $target => $origins)
{
	printf("  %-32s %s\n", $target, implode(', ', array_unique($origins)));
}

exit($problems ? 1 : 0);
