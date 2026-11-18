<?php
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

/**
 * ko8 · reglas de compatibilidad con PHP 8.
 *
 * Solo reglas que NO cambian comportamiento. Nada de:
 *  - strict_types (rompe coerciones de apps legacy)
 *  - tipos de retorno en metodos sobreescribibles (rompe subclases)
 *  - modernizaciones cosmeticas (str_contains, first-class callables...) que
 *    generan diffs enormes sin ganar compatibilidad.
 */
return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withPaths([
        __DIR__.'/system/classes',
        __DIR__.'/modules',
    ])
    ->withSkip([
        // The unittest module is superseded by tests/ (PHPUnit 11). It is kept
        // only so old installations do not break on a missing file.
        __DIR__.'/modules/unittest',
    ])
    ->withRules([
        // PHP 8.4: `Type $x = NULL` -> `?Type $x = NULL`
        ExplicitNullableParamTypeRector::class,
    ]);
