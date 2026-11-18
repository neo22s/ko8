# Testing

ko8 ships two PHPUnit 11 suites.

```bash
composer test          # the ko8 suite: tests/unit      (~12 tests)
composer test:legacy   # the inherited Kohana/Koseven suite (~1440 tests)
composer test:all      # both
```

## The ko8 suite

`tests/unit` is new code: small, typed, one behaviour per test.

```bash
vendor/bin/phpunit --filter ArrTest
```

Bootstrap is `tests/bootstrap.php`, 50 lines: constants, Composer autoloader, `Kohana::init()`, one route. No `unittest` module, no web UI, no module cascade.

`tests/TestCase.php` is the base class. It carries the environment helpers the old suite used (`setEnvironment()`, `cleanCacheDir()`, `dirSeparator()`) because they are genuinely useful, and the assertions PHPUnit removed after 9.x (`assertTag()`, `assertAttributeSame()`, `assertInternalType()`, …) because the inherited suite depends on them.

## The inherited suite

The 2016 tests are not rewritten by hand. `tools/import-legacy-tests.php` copies them from `system/tests` and `modules/*/tests` into `tests/legacy/` and applies the mechanical changes PHPUnit 10 requires:

| Before | After |
|---|---|
| `class Kohana_ArrTest extends Unittest_TestCase` | `class LegacyCorekohana_ArrTest extends KO8_TestCase` |
| `@dataProvider foo` | `#[DataProvider('foo')]` |
| `@test` | `#[Test]` |
| `@covers X::y` | `#[CoversMethod('X', 'y')]` |
| `@expectedException E` | `$this->expectException(E::class)` |
| `function providerX()` | `public static function providerX()` |
| `setUp()` | `setUp(): void` |
| `createMock($c, $methods, $args, $name, $ctor)` | `$this->mock(...)` |
| `->setMethods([...])` | `$this->mock(...)` |
| `assertInternalType()` | `assertIs*()` / `assertNull()` |
| `assertContains($s, $string)` | `$this->assertContainsCompat()` |
| `> en_US.utf8` | `setlocale()` with a candidate list |

Run it again after changing the framework:

```bash
php tools/import-legacy-tests.php
composer test:legacy
```

Each file is prefixed with its suite and sub-path (`LegacyCachecache_request_client_CacheTest`) because PHPUnit 10 requires the class name to match the file name, and the old suite reused names across directories.

### Intentional deviations

A handful of inherited assertions describe things that stopped being true before PHP 8. They are rewritten, not deleted, and the reason is in the script:

- `strcmp()` returns `-1/0/1` since PHP 8.2, not the byte delta. The UTF8 tests expected `4` and `6`.
- `Kohana_Exception::text()` prints `DOCROOT/...`, not `SYSPATH/tests/...`. The test now derives the expected path with `Debug::path(__FILE__)`.
- The `unittest` module no longer exists, so the "modules contains unittest" assertion now checks an enabled module.
- `EncryptTest` is not imported at all: its vectors describe `mcrypt`, removed in PHP 7.2. `src/Kohana/Encrypt/Engine/Mcrypt.php` is not shipped.
- `__toString()` cannot be mocked in PHPUnit 11, so `ResponseTest` uses `KO8_TestCase::stringable()`.

## The API browser is a test

`tests/unit/ApiBrowserTest.php` renders real `/guide-api/<class>` pages through
the request stack. It exists because that page is reflection over every loaded
class: it is the cheapest way to find a dynamic property, a bad return type or a
deprecated call anywhere in the framework. A class that renders in the browser
is a class that compiles cleanly on PHP 8.5.

Everything else — 1441 tests, 3030 assertions — runs as written.

## Known gaps

- No database in CI by default. `Database` and `ORM` are only exercised where they do not need a connection.
- 89 tests are skipped: they need Memcache, APC, WinCache, SQLite or network access.
- `getMockForAbstractClass()` and mocking a class with a method named `method` are deprecated in PHPUnit 11 and will break in PHPUnit 12. That affects a handful of tests in `Request` and `Session`.

## Documentation checks

Two scripts guard the guide, and both run without a browser:

```bash
php tools/check-guide-links.php          # every Markdown link and docblock link
php tools/crawl-docs.php https://koseven.test /index.php/guide
```

`check-guide-links.php` resolves links the way the guide does (relative to the
module root, `api/Class` for the API browser) and fails on anything that points
at a page that does not exist. `crawl-docs.php` renders the site and follows
every `href`, which is the only way to catch a link that is relative to the
wrong base.

This matters because the guide is served from two different URLs: a page lives
at `/guide/<module>/<page>` and a docblock lives at `/guide-api/<class>`. A link
written as `guide-api/Foo` is correct in the first and broken in the second.
`Kohana_Kodoc_Links` resolves every link to an absolute path for that reason.

## Coverage

```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text
```
