<?php
/**
 *
 * @package    Kohana/Userguide
 * @author     Kohana Team
 * @copyright  (c) Kohana Team
 * @license    https://koseven.ga/LICENSE.md
 */
class LegacyUserguideKodocTest extends KO8_TestCase
{
	public static function provider_parse_basic(){
		return [
			[
<<<'COMMENT'
/**
 * Description
 */
COMMENT
,
				["<p>Description</p>\n", []],
			],
			[
<<<'COMMENT'
/**
 * Description spanning
 * multiple lines
 */
COMMENT
,
				["<p>Description spanning\nmultiple lines</p>\n", []],
			],
			[
<<<'COMMENT'
/**
 * Description including
 *
 *     a code block
 */
COMMENT
,
				["<p>Description including</p>\n\n<pre><code>a code block\n</code></pre>\n", []],
			],
			[
<<<'COMMENT'
	/**
	 * Indented
	 */
COMMENT
,
				["<p>Indented</p>\n", []],
			],
			[
<<<'COMMENT'
/**
 * @tag Content
 */
COMMENT
,
				['', ['tag' => ['Content']]],
			],
			[
<<<'COMMENT'
/**
 * @tag Multiple
 * @tag Tags
 */
COMMENT
,
				['', ['tag' => ['Multiple', 'Tags']]],
			],
			[
<<<'COMMENT'
/**
 * Description with tag
 * @tag Content
 */
COMMENT
,
				[
					"<p>Description with tag</p>\n",
					['tag' => ['Content']],
				],
			],
			[
<<<'COMMENT'
/**
 * @trailingspace
 */
COMMENT
,
				['', ['trailingspace' => ['']]],
			],
			[
<<<'COMMENT'
/**
 * @tag Content that spans
 * multiple lines
 */
COMMENT
,
				[
					'',
					['tag' => ["Content that spans\nmultiple lines"]],
				],
			],
			[
<<<'COMMENT'
/**
 * @tag Content that spans
 *    multiple lines indented
 */
COMMENT
,
				[
					'',
					['tag' => ["Content that spans\n   multiple lines indented"]],
				],
			],
		];
	}

	/**
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana_Kodoc', 'parse')]
	 *
	 *
	 * @param   string  $comment    Argument to the method
	 * @param   array   $expected   Expected result
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_parse_basic')]
	public function test_parse_basic($comment, $expected)
	{
		$this->assertSame($expected, Kodoc::parse($comment));
	}

	public static function provider_parse_tags(){
		$route_api = Route::get('docs/api');

		return [
			[
<<<'COMMENT'
/**
 * @access public
 */
COMMENT
,
				['', []],
			],
			[
<<<'COMMENT'
/**
 * @copyright Some plain text
 */
COMMENT
,
				['', ['copyright' => ['Some plain text']]],
			],
			[
<<<'COMMENT'
/**
 * @copyright (c) 2008-2017 Kohana Team
 */
COMMENT
,
				['', ['copyright' => ['&copy; 2008-2017 Kohana Team']]],
			],
			[
<<<'COMMENT'
/**
 * @license Kohana
 */
COMMENT
,
				['', ['license' => ['Kohana']]],
			],
			[
<<<'COMMENT'
/**
 * @license http://kohanaframework.org/license
 */
COMMENT
,
				['', ['license' => ['<a href="http://kohanaframework.org/license">http://kohanaframework.org/license</a>']]],
			],
			[
<<<'COMMENT'
/**
 * @link http://kohanaframework.org
 */
COMMENT
,
				['', ['link' => ['<a href="http://kohanaframework.org">http://kohanaframework.org</a>']]],
			],
			[
<<<'COMMENT'
/**
 * @link http://kohanaframework.org Description
 */
COMMENT
,
				['', ['link' => ['<a href="http://kohanaframework.org">Description</a>']]],
			],
			[
<<<'COMMENT'
/**
 * @see Arr
 */
COMMENT
,
				[
					'',
					[
						'see' => [
							'<a href="'.URL::site(
								$route_api->uri(['class' => 'Arr'])
							).'">Arr</a>',
						],
					],
				],
			],
			[
<<<'COMMENT'
/**
 * @see Arr::method
 */
COMMENT
,
				[
					'',
					[
						'see' => [
							'<a href="'.URL::site(
								$route_api->uri(['class' => 'Arr']).'#method'
							).'">Arr::method</a>',
						],
					],
				],
			],
			[
<<<'COMMENT'
/**
 * @throws Exception
 */
COMMENT
,
				[
					'',
					[
						'throws' => [
							'<a href="'.URL::site(
								$route_api->uri(['class' => 'Exception'])
							).'">Exception</a>',
						],
					],
				],
			],
			[
<<<'COMMENT'
/**
 * @throws Exception During failure
 */
COMMENT
,
				[
					'',
					[
						'throws' => [
							'<a href="'.URL::site(
								$route_api->uri(['class' => 'Exception'])
							).'">Exception</a> During failure',
						],
					],
				],
			],
			[
<<<'COMMENT'
/**
 * @uses Arr
 */
COMMENT
,
				[
					'',
					[
						'uses' => [
							'<a href="'.URL::site(
								$route_api->uri(['class' => 'Arr'])
							).'">Arr</a>',
						],
					],
				],
			],
			[
<<<'COMMENT'
/**
 * @uses Arr::method
 */
COMMENT
,
				[
					'',
					[
						'uses' => [
							'<a href="'.URL::site(
								$route_api->uri(['class' => 'Arr']).'#method'
							).'">Arr::method</a>',
						],
					],
				],
			],
		];
	}

	/**
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana_Kodoc', 'format_tag')]
	 * #[\PHPUnit\Framework\Attributes\CoversMethod('Kohana_Kodoc', 'parse')]
	 *
	 *
	 * @param   string  $comment    Argument to the method
	 * @param   array   $expected   Expected result
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_parse_tags')]
	public function test_parse_tags($comment, $expected)
	{
		$this->assertSame($expected, Kodoc::parse($comment));
	}

	/**
	 * Provides test data for test_transparent_classes
	 * @return array
	 */
	public static function provider_transparent_classes(){
		return [
			// Kohana_Core is a special case
			['Kohana','Kohana_Core',NULL],
			['Controller_Template','Kohana_Controller_Template',NULL],
			['Controller_Template','Kohana_Controller_Template',
				['Kohana_Controller_Template'=>'Kohana_Controller_Template',
					'Controller_Template'=>'Controller_Template']
			],
			[FALSE,'Kohana_Controller_Template',
				['Kohana_Controller_Template'=>'Kohana_Controller_Template']],
			[FALSE,'Controller_Template',NULL],
		];
	}

	/**
	 * Tests Kodoc::is_transparent
	 *
	 * Checks that a selection of transparent and non-transparent classes give expected results
	 *
	 * @param mixed $expected
	 * @param string $class
	 * @param array $classes
	 */#[\PHPUnit\Framework\Attributes\Group('kohana.userguide.3529-configurable-transparent-classes')]
	#[\PHPUnit\Framework\Attributes\DataProvider('provider_transparent_classes')]
	public function test_transparent_classes($expected, $class, $classes)
	{
		$result = Kodoc::is_transparent($class, $classes);
		$this->assertSame($expected,$result);
	}
}
