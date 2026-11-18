<?php

/**
 * ko8 userguide module.
 *
 * Routes:
 *   /guide                                    index of documented modules
 *   /guide/<module>(/<page>)                  rendered Markdown from guide/
 *   /guide-api(/<class>)                      reflection-based API browser
 *   /guide-media/<file>                       CSS, images and screenshots
 *
 * @package    Ko8/Userguide
 */

// Static file serving (CSS, images, screenshots)
Route::set('docs/media', 'guide-media(/<file>)', ['file' => '.+'])
	->defaults([
		'controller' => 'Userguide',
		'action'     => 'media',
		'file'       => NULL,
	]);

// API browser, if enabled
if (Kohana::$config->load('userguide.api_browser') === TRUE)
{
	Route::set('docs/api', 'guide-api(/<class>)', ['class' => '[a-zA-Z0-9_]+'])
		->defaults([
			'controller' => 'Userguide',
			'action'     => 'api',
			'class'      => NULL,
		]);
}

// User guide pages, in modules
Route::set('docs/guide', 'guide(/<module>(/<page>))', ['page' => '.+'])
	->defaults([
		'controller' => 'Userguide',
		'action'     => 'docs',
		'module'     => '',
	]);

/**
 * The bundled Markdown parser predates PSR-4 and does not follow the
 * underscore convention, so it needs its own autoloader.
 */
class Markdown_Autoloader
{
	public static function autoload($class)
	{
		if ($class === 'Markdown_Parser' OR $class === 'MarkdownExtra_Parser')
		{
			include_once Kohana::find_file('vendor', 'markdown/markdown');
		}
	}
}

spl_autoload_register(['Markdown_Autoloader', 'autoload']);
