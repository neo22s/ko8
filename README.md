# ko8

**The Koseven / Kohana 3.3 API on modern PHP.** Lightweight HMVC, cascading filesystem, zero magic. No DI container, no annotations, no 40-package dependency tree.

```bash
composer create-project ko8/ko8 myapp
```

Requires PHP 8.1+. Runs on 8.1, 8.2, 8.3, 8.4 and 8.5.

## Why

Koseven kept Kohana alive for the PHP 7 era. It was archived in 2026. Its job — getting legacy Kohana applications onto a supported PHP — is still unfinished for anyone on PHP 8.

ko8 finishes that job:

- **Same API.** `Model_*`, `Controller_*`, `Route::set()`, `ORM::factory()`, `Kohana::$config`, the cascading filesystem and the transparent class extension all work unchanged.
- **PHP 8 clean.** No deprecations on 8.1–8.5. No `mcrypt`, no `utf8_decode`, no `strftime`, no `each()`.
- **Small.** ~17k lines of core. You can read all of it in an afternoon.
- **Modern tooling.** Composer, PHPUnit 11, Rector, Tailwind. No Travis, no Docker image, no vendored 2011 Markdown parser.

## Status

| | |
|---|---|
| PHP | 8.1 – 8.5 |
| Tests | PHPUnit 11, `composer test` |
| Licence | BSD-3-Clause |

This is an early alpha. The framework API is stable because it is Koseven's; the packaging, docs and test suite are new.

## Layout

```
application/    your code: controllers, models, views, config
modules/        optional components (database, orm, auth, cache, image, minion…)
system/         the framework
  classes/      Kohana_* implementation + transparent extension shims
  src/Ko8/      PSR-4 namespace for new code
public/         document root (index.php, install.php)
tests/          PHPUnit 11 suite
assets/         Tailwind source
```

## Quick start

1. `composer install`
2. Point your web server at `public/`
3. Open `/install.php` and fix whatever it reports
4. Set `Cookie::$salt` in `application/bootstrap.php`
5. Delete `public/install.php`

## Migrating from Koseven

Read [docs/migration.md](docs/migration.md). Short version: replace `system/` and `modules/`, keep `application/`. Most applications need six files touched.

Wrong answer first: renaming classes to `Ko8_*` would break every app for no functional gain. `Kohana_*` stays; `Ko8\` is where new code goes.

## Tests

```bash
composer test
```

The suite has no in-app runner, no web UI and no module bootstrap. `tests/bootstrap.php` is 30 lines.

## Assets

The compiled CSS is committed, so production does not need Node:

```bash
npm install
npm run build
```

## Licence

BSD-3-Clause, inherited from Kohana and Koseven. See [LICENSE.md](LICENSE.md).
