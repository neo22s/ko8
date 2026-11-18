<?php
/**
 * Crawl the docs and check every internal link the way a browser resolves it.
 *
 * The point is relative URLs: from /index.php/guide-api/Foo a link written as
 * "guide-api/Bar" becomes /index.php/guide-api/guide-api/Bar, which is a 404
 * that hand-testing never finds.
 *
 * Uso: php tools/crawl-docs.php [base-url] [start-path]
 */
declare(strict_types=1);

$base  = $argv[1] ?? 'https://koseven.test';
$start = $argv[2] ?? '/index.php/guide-api';

/**
 * Resolve a link against the page it was found on, like a browser.
 */
function resolve_url(string $base, string $page, string $href): ?string
{
	$href = trim($href);

	if ($href === '' OR str_starts_with($href, '#') OR str_starts_with($href, 'mailto:'))
	{
		return NULL;
	}

	if (str_starts_with($href, '//') OR preg_match('~^[a-z][a-z0-9+.-]*://~i', $href))
	{
		return NULL;
	}

	$origin = parse_url($base, PHP_URL_SCHEME).'://'.parse_url($base, PHP_URL_HOST);

	if (str_starts_with($href, '/'))
	{
		return $origin.$href;
	}

	// Relative to the directory of the current page.
	$path = parse_url($page, PHP_URL_PATH) ?: '/';
	$dir  = substr($path, 0, strrpos($path, '/') + 1);

	return $origin.normalize_path($dir.$href);
}

/**
 * Collapse "." and ".." segments the way a browser does before sending.
 */
function normalize_path(string $path): string
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

	return '/'.implode('/', $segments);
}

/**
 * Fetch a URL, return [status, content-type, body].
 *
 * @return array{0:int,1:string,2:string}
 */
function fetch(string $url): array
{
	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_FOLLOWLOCATION => FALSE,
		CURLOPT_TIMEOUT        => 30,
		CURLOPT_SSL_VERIFYPEER => FALSE,
		CURLOPT_SSL_VERIFYHOST => FALSE,
	]);

	$body   = (string) curl_exec($ch);
	$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$type   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

	// curl_close() is a no-op since PHP 8.0 and deprecated in 8.5.

	return [$status, $type, $body];
}

$queue    = [$start];
$seen     = [];
$referrer = [];
$broken   = [];
$checked  = 0;

while ($queue)
{
	$path = array_shift($queue);

	if (isset($seen[$path]) OR count($seen) > 400)
	{
		continue;
	}

	$seen[$path] = TRUE;

	[$status, $type, $body] = fetch($base.$path);

	$checked++;

	if ($status !== 200)
	{
		[$from, $href] = $referrer[$path] ?? [NULL, NULL];

		$broken[] = [$from, $path, $href, $status];

		continue;
	}

	if ( ! str_contains($type, 'text/html'))
	{
		continue;
	}

	if ( ! preg_match_all('/href="([^"]+)"/i', $body, $matches))
	{
		continue;
	}

	foreach (array_unique($matches[1]) AS $href)
	{
		$absolute = resolve_url($base, $base.$path, html_entity_decode($href, ENT_QUOTES));

		if ($absolute === NULL)
		{
			continue;
		}

		$parsed = parse_url($absolute);

		// Only crawl this host and the docs.
		if (($parsed['host'] ?? '') !== parse_url($base, PHP_URL_HOST))
		{
			continue;
		}

		$next = $parsed['path'] ?? '/';

		if (const_path_is_docs($next))
		{
			if ( ! isset($seen[$next]))
			{
				$referrer[$next] = [$path, $href];
				$queue[] = $next;
			}

			continue;
		}

		// Follow anything else once, to prove it does not 404.
		if ( ! isset($seen[$next]))
		{
			[$other_status] = fetch($base.$next);
			$checked++;

			if ($other_status >= 400)
			{
				$broken[] = [$path, $next, $href];
			}
		}
	}
}

/**
 * Is this path part of the documentation tree?
 */
function const_path_is_docs(string $path): bool
{
	return str_contains($path, '/guide') OR $path === '/index.php';
}

echo "pages checked: $checked\n";
echo "broken: ".count($broken)."\n";

foreach ($broken AS [$from, $target, $href, $status])
{
	printf("  %-3d %-44s %-56s %s\n", $status, $from ?? '-', $target, trim((string) $href));
}

exit($broken ? 1 : 0);
