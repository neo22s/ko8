<?php
/**
 * Link resolution for the documentation.
 *
 * Markdown pages and docblocks both end up as HTML under /guide, and every link
 * is written relative to something: the current page, its module, or the guide
 * root. Resolving them at render time is what keeps /guide and /guide-api
 * honest, and what lets a link to a missing page degrade to plain text instead
 * of a 404.
 *
 * @package    Ko8/Userguide
 * @category   Documentation
 */
class Kohana_Kodoc_Links {

	/**
	 * Site path of the guide, e.g. "/index.php/guide".
	 *
	 * @var string
	 */
	public static $guide_url = NULL;

	/**
	 * Site path of the API browser, e.g. "/index.php/guide-api".
	 *
	 * @var string
	 */
	public static $api_url = NULL;

	/**
	 * Module the current page belongs to, e.g. "kohana". Empty for docblocks,
	 * whose links already start with the module name.
	 *
	 * @var string
	 */
	public static $module = '';

	/**
	 * Current page inside the module, e.g. "mvc/controllers". Empty for the
	 * module index and for docblocks.
	 *
	 * @var string
	 */
	public static $page = '';

	/**
	 * Site path of the media route, e.g. "/index.php/guide-media".
	 *
	 * @var string
	 */
	public static $media_url = NULL;

	/**
	 * Resolve an image target to an absolute site path.
	 *
	 * Images live under media/guide/<module>/ and the cascade decides which one
	 * wins, so the file has to exist before a path is emitted.
	 *
	 * @param   string  $target  what the author wrote
	 * @return  string|FALSE
	 */
	public static function image($target)
	{
		$target = trim(preg_replace('/\s+"[^"]*"$/', '', $target));

		if ($target === '' OR preg_match('~^[a-z][a-z0-9+.-]*://~i', $target) OR str_starts_with($target, '//'))
		{
			return FALSE;
		}

		$target = ltrim($target, '/');

		$candidates = [];

		if (static::$module !== '')
		{
			$candidates[] = static::$module.'/'.$target;
		}

		$candidates[] = $target;

		foreach ($candidates AS $candidate)
		{
			$extension = pathinfo($candidate, PATHINFO_EXTENSION);
			$path      = substr($candidate, 0, -(strlen($extension) + 1));

			if (Kohana::find_file('media/guide', $path, $extension))
			{
				return static::media_url().'/'.$candidate;
			}
		}

		return FALSE;
	}

	/**
	 * Site path of the guide, computed from the current configuration when no
	 * controller has set one.
	 *
	 * Values are deliberately not cached: `Kohana::$index_file` and `base_url`
	 * can change between requests (and between tests), and a stale prefix is how
	 * a link ends up pointing at /guide-api/guide-api/....
	 */
	public static function guide_url()
	{
		return static::$guide_url ?? URL::site('guide');
	}

	/**
	 * Site path of the API browser.
	 */
	public static function api_url()
	{
		return static::$api_url ?? URL::site('guide-api');
	}

	/**
	 * Site path of the guide media route.
	 */
	public static function media_url()
	{
		return static::$media_url ?? URL::site('guide-media');
	}

	/**
	 * Resolve a link target to an absolute site path.
	 *
	 * Every scheme the documentation uses is supported:
	 *
	 *     [install](install)              relative to the current page
	 *     [config](kohana/config)         from the guide root (docblocks)
	 *     [cache](../cache)               up one directory
	 *     [results](/database/results)    absolute inside the guide
	 *     [Request](api/Request)          the API browser
	 *
	 * @param   string  $target  what the author wrote
	 * @param   string  $kind    "guide" or "api"
	 * @return  string|FALSE     absolute path, or FALSE when it does not exist
	 */
	public static function resolve($target, $kind = 'guide')
	{
		$target = trim($target);

		// Drop an optional Markdown title.
		$target = preg_replace('/\s+"[^"]*"$/', '', $target);

		$anchor = '';

		if (preg_match('/^([^#?]*)([#?].*)$/', $target, $matches))
		{
			$target = $matches[1];
			$anchor = $matches[2];
		}

		if ($target === '')
		{
			return FALSE;
		}

		// A link may reach the API browser through a relative path, e.g.
		// `../api/Request` from kohana/mvc/controllers.
		if (str_starts_with(static::clean($target), 'api/'))
		{
			$target = static::clean($target);
		}

		// The API browser: `api/ClassName`, `api/Class#method`.
		if (str_starts_with($target, 'api/'))
		{
			$class = static::canonical_name(substr($target, 4));

			return ($class === FALSE) ? FALSE : static::api_url().'/'.$class.$anchor;
		}

		// An absolute path is already a site path.
		if (str_starts_with($target, '/'))
		{
			$page = static::clean(ltrim($target, '/'));

			return static::page_exists($page) ? static::guide_url().'/'.$page.$anchor : FALSE;
		}

		// Guide links are relative to the *module root*, not to the page. That is
		// the convention the whole guide follows: from kohana/mvc/views,
		// "[filesystem](files)" means kohana/files, and "../cache" reaches
		// another module. Resolving against the page directory breaks hundreds
		// of links, so it is deliberately not done.
		$page = static::clean(static::$module.'/'.$target);

		// Old guide links were rooted at "guide/": `../../guide/orm` means the
		// ORM module index, not a page called "guide/orm".
		if (str_starts_with($page, 'guide/'))
		{
			$page = substr($page, 6);
		}

		return static::page_exists($page) ? static::guide_url().'/'.$page.$anchor : FALSE;
	}

	/**
	 * The real name of a class, or FALSE when it does not exist.
	 *
	 * PHP class names are case-insensitive, so `form` resolves to `Form`. The
	 * API browser redirects to the canonical spelling; resolving it here saves
	 * a redirect per link.
	 *
	 * @param   string  $class
	 * @return  string|FALSE
	 */
	public static function canonical_name($class)
	{
		if ( ! static::class_exists($class))
		{
			return FALSE;
		}

		return (new ReflectionClass($class))->getName();
	}

	/**
	 * Link to a class page.
	 */
	public static function class_exists($class)
	{
		if ( ! class_exists($class) AND ! interface_exists($class) AND ! trait_exists($class))
		{
			return FALSE;
		}

		// The API browser fabricates unknown classes; they are not real pages.
		return ! is_subclass_of($class, 'Kodoc_Missing') AND $class !== 'Kodoc_Missing';
	}

	/**
	 * Does "module/page" name a guide page that exists?
	 */
	public static function page_exists($path)
	{
		$path = static::clean($path);

		[$module, $page] = array_pad(explode('/', $path, 2), 2, '');

		if ($module === '')
		{
			return FALSE;
		}

		if ($page === '')
		{
			$page = 'index';
		}

		return (bool) Kohana::find_file('guide', $module.'/'.$page, 'md');
	}

	/**
	 * Collapse "." and ".." segments, like a browser does with a URL path.
	 */
	public static function clean($path)
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

}
