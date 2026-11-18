# Upgrading to ko8

This page covers the whole road: Kohana 3.3 → Koseven 3.3 → ko8 8.0. If you are already on Koseven, jump to [Upgrading from Koseven](#upgrading-from-koseven).

The one thing to know first: **the API does not change.** `Kohana_*` classes, the cascading filesystem, `Route::set()`, `ORM::factory()`, `Kohana::$config` and transparent extension all behave as they did. You are changing the layer that talks to PHP, not the layer your application talks to.

## Version map

| | Kohana | Koseven | ko8 |
|---|---|---|---|
| Release | 3.3.6 | 3.3.9 `karlsruhe` | **8.0.0** `genesis` |
| PHP | 5.3 – 7.0 | 7.0 – 8.0 | **8.1 – 8.5** |
| `Kohana::VERSION` | `3.3.6` | `3.3.9` | `8.0.0` |
| `Kohana::KOSEVEN_COMPAT` | — | — | `3.3.9` |

`Kohana::KOSEVEN_COMPAT` is the constant to compare when code needs to know which API it is running against. It does not exist in Koseven, so guard it with `defined()` if the same code has to run on both.

## Upgrading from Kohana to Koseven

Koseven took Kohana 3.3.6 and kept it alive through the PHP 7 era. Four changes were breaking:

- **`Kohana_Kohana_Exception`** — methods that took `Exception $e` now take `$e`. If you extend the class, match the signature.
- **`Kohana_URL`** — `URL::site()` gained `$subdomain`. A subclass without it is a fatal declaration error.
- **`encrypt` is a module** — add it to `Kohana::modules()`:
  ```php
  'encrypt' => MODPATH.'encrypt',
  ```
- **The `MySQL` driver was removed** — set `'type' => 'MySQLi'` in `config/database.php`.

Koseven also moved `Pagination` out of the core into `modules/pagination`.

## Upgrading from Koseven

### 1. Run the doctor

`bin/ko8-doctor.php` reads your application without executing it:

```bash
php bin/ko8-doctor.php application
```

```
ko8 doctor · 939 files scanned

WILL BREAK (1)
  oc/common/classes/oc/i18n.php:449
    money_format() was removed in PHP 8.0.
    fix: NumberFormatter::formatCurrency()

Summary: 1 will break, 0 probably break, 0 to check.
```

Exit codes are `0` clean, `1` something will break, `2` only warnings. It understands the difference between `$this->money_format()` (your method) and `money_format()` (the built-in that PHP 8 removed), so the report is short enough to act on.

Every fix it suggests is expanded below. `docs/migration.md` in the repository has the same content in one flat page, for reading offline.

### 2. Replace the framework

```bash
composer require ko8/ko8:^8.0 --with-all-dependencies
```

`ko8/ko8` replaces `kohana/core 3.3.*` and `koseven/koseven 3.3.*`, so packages that depend on either are satisfied. Keep `application/` untouched.

### 3. What ko8 already fixed

None of this is your work; it is why you are moving:

| Issue | Where |
|---|---|
| `array_key_exists()` on `Config_Group` threw `TypeError` | `Arr::path()` |
| `mysqli` throws exceptions since 8.1, the driver expected `FALSE` | `Database_MySQLi::connect()` |
| 90 implicitly nullable parameters | whole codebase |
| `E_STRICT` removed in 8.4 | `Kohana_Exception::$php_errors` |
| `strftime()`, `utf8_decode()`, `each()` gone or deprecated | `Date`, `UTF8`, codebase |
| ORM models implementing `Serializable`, deprecated in 8.1 | `ORM::__serialize()` / `__unserialize()` |
| `encrypt` defaulting to mcrypt, a fatal at config load | `modules/encrypt/config/encrypt.php` |

### 4. What you must change

**`mcrypt`.** The extension was removed in PHP 7.2 and its constants are undefined. Use the OpenSSL engine:

```php
$encrypt = new Encrypt([
    'type'   => 'openssl',
    'key'    => $key,
    'cipher' => 'AES-128-CBC',
]);
```

Old data still decrypts: `RIJNDAEL_128` + `MCRYPT_MODE_NOFB` is `AES-128-OFB`, and the payload is `base64(iv . ciphertext)`. The repository's `docs/migration.md` has the shim, ready to paste.

**`URL::site()` overrides.** Keep the fourth parameter. This is the most common fatal in a real application.

**`count()` on `null`.** It is a `TypeError` now, not a warning. `if ($products AND count($products))`.

**Dynamic properties.** Undeclared `$this->foo = …` is deprecated in 8.2. Declare the property, define `__set()`, or add `#[\AllowDynamicProperties]` to the class.

**`Cookie::$salt`** and **`application/config/url.php`** with `trusted_hosts`. Koseven requires both, and the doctor reports them.

### 5. Verify

```bash
composer test          # ko8's own suite
composer test:legacy   # the inherited Kohana/Koseven suite, 1441 tests
php bin/ko8-doctor.php application
```

Then open the application and follow the error page if something fails. It tells you the file and the line, and it inlines its own CSS so it renders even when the document root is wrong.

## If it does not work

The error page is the first answer. If it is blank, `public/install.php` is the second: it checks the PHP version, extensions, writability and the document root, and it runs before the framework boots.

If a class will not load, the message names both signatures. If a view throws, it names the file and line. If nothing renders at all, the rewrite rules or the document root are the problem, not ko8.
