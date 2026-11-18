# Migrating from Koseven

ko8 keeps the Koseven 3.3 API. Migration is not a rewrite: replace the framework, run the doctor, and touch the handful of places where PHP 8 changed behaviour.

> **Before you start.** Run `public/install.php`. It checks the environment in seconds and often explains the problem before you read a single log line.

## 0. Versions

| | Kohana | Koseven | ko8 |
|---|---|---|---|
| Latest release | 3.3.6 | 3.3.9 (`karlsruhe`) | **8.0.0** (`genesis`) |
| PHP | 5.3 – 7.0 | 7.0 – 8.0 | **8.1 – 8.5** |
| API | Kohana 3.3 | Kohana 3.3 | **Koseven 3.3.9** |
| `Kohana::VERSION` | `3.3.6` | `3.3.9` | `8.0.0` |
| `Kohana::version()` | `Kohana 3.3.6 (…)` | `Koseven 3.3.9 (karlsruhe)` | `ko8 8.0.0 (genesis)` |

The number changed because the code did. `3.3.9` means "Koseven 3.3.9"; ko8 is a different codebase on a different PHP, and two projects answering with the same version string is a support problem.

Nothing else changed with it. An application that asks *"is this Koseven's API?"* should not compare `VERSION` — it should compare the constant that exists for exactly that question:

```php
if (version_compare(Kohana::KOSEVEN_COMPAT, '3.3.9', '>='))
{
    // The Koseven 3.3.9 API is available, whichever framework provides it.
}
```

| Constant | Value | Means |
|---|---|---|
| `Kohana::VERSION` | `8.0.0` | This framework's release |
| `Kohana::CODENAME` | `genesis` | This framework's codename |
| `Kohana::KOSEVEN_COMPAT` | `3.3.9` | The Koseven API it implements |

`Kohana::KOSEVEN_COMPAT` does not exist in Koseven, so guard it with `defined()` if the same application has to run on both during a transition:

```php
$compat = defined('Kohana::KOSEVEN_COMPAT') ? Kohana::KOSEVEN_COMPAT : Kohana::VERSION;
```

## 1. Requirements

| | Koseven | ko8 |
|---|---|---|
| PHP | 7.0+ | **8.1+** |
| mbstring | recommended | **required** |
| Composer | optional | required |
| `mcrypt` | optional | **removed** |
| `mysql` | removed | **removed** |

## 2. Run the doctor first

`bin/ko8-doctor.php` reads your application without executing it and tells you what will break, with the file, the line and the fix:

```bash
php vendor/ko8/ko8/bin/ko8-doctor.php application
```

```
ko8 doctor · 939 files scanned

WILL BREAK (1)
  oc/common/classes/oc/i18n.php:449
    money_format() was removed in PHP 8.0.
    fix: NumberFormatter::formatCurrency()

Summary: 1 will break, 0 probably break, 0 to check.
```

Exit codes: `0` clean, `1` something will break, `2` only warnings. `--json` gives the same report for a CI job.

It looks for what actually stops an application:

| Rule | Why |
|---|---|
| `function-*` | `each()`, `money_format()`, `create_function()`, `ereg*()`, `mysql_*()`, `mcrypt_*()` no longer exist |
| `dynamic-property` | An undeclared `$this->foo = …` is deprecated in PHP 8.2 |
| `implicit-nullable` | `array $x = NULL` is deprecated in PHP 8.4; write `?array $x = NULL` |
| `signature-*` | An override with fewer parameters than the parent is a fatal declaration error |
| `cookie-salt` | `Cookie::$salt = NULL` makes every `Cookie::set()` throw |
| `trusted-hosts` | Koseven rejects unknown hosts in `URL::base()` without `config/url.php` |

The scan is token based, so `$this->money_format()` and `function money_format()` are not confused with the removed built-in, and `$('.x').each(` inside a template string is not a call. That is the difference between a report you act on and one you ignore.

It does **not** report `count()` on a possibly-null variable. That is a real PHP 8 `TypeError`, but it cannot be told apart from a correct `count($array)` without type information, and flagging every call buries the findings that matter. Grep for it instead:

```bash
grep -rnE 'count\(\s*\$' application/ | grep -v 'count(\$this'
```

## 3. Replace the framework

```bash
composer require ko8/ko8:^8.0 --with-all-dependencies
```

`ko8/ko8` declares `replace: kohana/core 3.3.*` and `koseven/koseven 3.3.*`. A package that requires `koseven/koseven:3.3.9` is satisfied by ko8 and will not be installed alongside it.

**One caveat, because `replace` is not magic.** Composer honours the constraint you asked for, not the one you meant:

```bash
composer require ko8/ko8:^8.0
#   koseven/koseven 3.3.9 is replaced — fine

composer require koseven/koseven:^3.3
#   asks for the real package by name; Composer installs it
```

If a dependency pins `koseven/koseven` exactly (`"koseven/koseven": "3.3.9"`), the replace still wins. If it requires a version ko8 does not claim, Composer will refuse and tell you so — that is the correct behaviour, and the fix is to widen the constraint there.

If you vendor the framework by hand (the classic `system/` + `modules/` layout):

```bash
mv system system.old
mv modules modules.old
cp -R vendor/ko8/ko8/system .
cp -R vendor/ko8/ko8/modules .
```

Keep `application/` untouched. That is the point.

## 4. What ko8 fixes for you

These were **fatal or wrong** on PHP 8 and are already handled:

| Issue | Where |
|---|---|
| `array_key_exists()` on a `Config_Group` threw `TypeError` | `Arr::path()` |
| `mysqli` throws `mysqli_sql_exception` since 8.1, driver expected `FALSE` | `Database_MySQLi::connect()` |
| `ReturnTypeWillChange` notices on 4 classes | `Validation`, `HTTP_Header`, `Config_Group`, `Database_Result` |
| `E_STRICT` deprecated in 8.4 | `Kohana_Exception::$php_errors` |
| `strftime()` deprecated in 8.1 | `Date::months()` |
| `utf8_decode()` deprecated in 8.2 | `UTF8::strlen()` |
| 90 implicitly nullable parameters | Rector, whole codebase |
| `Route::uri()` passing `NULL` to `rawurlencode()` | `Route::uri()` |
| `Kohana_Exception` with a `NULL` message | `Kohana_Exception` |
| `HTTP_Cache` hit counter reset on every request | `HTTP_Cache` |
| `Validation` with a `NULL` offset | `Validation::offsetGet()` |
| ORM models using `Serializable` (deprecated in 8.1) | `ORM::__serialize()` / `__unserialize()` |
| `modules/encrypt` defaulting to mcrypt (a fatal at config load) | `modules/encrypt/config/encrypt.php` |
| The API browser and guide emitting relative links, and therefore 404s | `Kohana_Kodoc_Links` |

You do not have to do anything for these.

## 5. What you must change

### 5.1 `encrypt` is a module

In Kohana 3.3.6 it lived in the core; in Koseven and ko8 it is `modules/encrypt`.

```php
// application/bootstrap.php
Kohana::modules([
    // ...
    'encrypt' => MODPATH.'encrypt',
]);
```

### 5.2 `mcrypt` is gone

`MCRYPT_MODE_*` and `MCRYPT_RIJNDAEL_*` are **undefined constants**, and an undefined constant is a fatal error in PHP 8 — not a warning.

```php
// before
$encrypt = new Encrypt($key, MCRYPT_MODE_NOFB, MCRYPT_RIJNDAEL_128);

// after
$encrypt = new Encrypt([
    'type'   => 'openssl',
    'key'    => $key,
    'cipher' => 'AES-128-CBC',
]);
```

ko8's default is already `openssl` / `AES-256-CBC`, and the old three-argument `new Encrypt($key, $mode, $cipher)` is mapped to it instead of throwing. `Encrypt_Engine_Mcrypt` still exists, but only to give a clear exception instead of a fatal.

**Your existing ciphertext still decrypts.** `mcrypt`'s `RIJNDAEL_128` + `MCRYPT_MODE_NOFB` is exactly `AES-128-OFB`. The payload shape is `base64(iv . ciphertext)`. ko8's `Encrypt` writes a different format (JSON + HMAC), so old values need a legacy read path:

```php
/**
 * Decrypt a value written by the mcrypt era (RIJNDAEL_128 + NOFB).
 */
function legacy_decrypt(string $payload, string $key): string|false
{
    $raw = base64_decode($payload, TRUE);

    if ($raw === FALSE OR strlen($raw) < 17)
    {
        return FALSE;
    }

    return openssl_decrypt(
        substr($raw, 16),
        'AES-128-OFB',
        $key,
        OPENSSL_RAW_DATA,
        substr($raw, 0, 16)
    );
}
```

Roll your quicklogin links and encrypted e-mails forward, then drop the shim.

### 5.3 `URL::site()` gained a fourth parameter

Koseven added `$subdomain` to `URL::site()` and `URL::base()`. If your application overrides either with the old signature, PHP 8 refuses to load the class:

```
Declaration of URL::site(...) must be compatible with Kohana_URL::site(...)
```

Add the parameter:

```php
public static function site($uri = '', $protocol = NULL, $index = TRUE, $subdomain = NULL)
```

This is the single most common fatal when moving a real app. **ko8 will never add parameters to overridable methods again** — that is rule number one.

### 5.4 `Kohana::$magic_quotes` is gone

Removed upstream because magic quotes died in PHP 5.4. Applications that still read it get `Access to undeclared static property`.

```php
// application/classes/Kohana.php
class Kohana extends Kohana_Core
{
    public static $magic_quotes = FALSE;   // compatibility shim
}
```

Or delete the check from your `sanitize()`.

### 5.5 `count()` on `null` is a `TypeError`

PHP 8 turned this warning into an exception. Audit views that assume a variable is always a collection:

```php
// before
<?php if (count($products)): ?>

// after
<?php if ($products AND count($products)): ?>
```

The usual culprit is a controller that leaves `$products = NULL` when a query returns nothing.

The doctor does not flag these, because it cannot tell `count($array)` from `count($maybeNull)` without type information. Grep for them:

```bash
grep -rnE 'count\(\s*\$' application/ themes/ | head -40
```

### 5.6 Implicit nullable parameters

`array $x = NULL` relies on a nullable PHP only infers. It is deprecated in 8.4
and emits a notice on every call:

```php
// before
public function find(Model_Order $order, array $options = NULL)

// after
public function find(Model_Order $order, ?array $options = NULL)
```

The doctor reports each one with the exact signature to write. It is mechanical
and safe: the type was already nullable, this just says so.

### 5.7 Declare your dynamic properties

Undeclared `$this->foo = …` is deprecated in PHP 8.2. Declare the property, add `__set()`, or opt the class out:

```php
#[\AllowDynamicProperties]
class Widget { /* … */ }
```

The doctor reports these, and it knows the difference between a class that inherits from something (where the parent may declare or intercept the property) and one that does not.

### 5.8 `Kohana_Exception::response()` may run before `$log` exists

If an error happens during bootstrap, `Kohana::$log` is still `NULL` and the error page dies with a second, unrelated fatal that hides the first one:

```php
// application/classes/Kohana/Exception.php
if (Kohana::$log)
{
    Kohana::$log->add(Log::ERROR, parent::text($e));
}
```

### 5.9 Declare your trusted hosts

Koseven rejects unknown hostnames in `URL::base()`:

```php
// application/config/url.php
return [
    'trusted_hosts' => [
        'example\.com',
        '.*\.example\.com',
    ],
];
```

### 5.10 Optional: `short_open_tag`

ko8's own views use full `<?php` tags. If your views use `<?`, either enable `short_open_tag` or convert them. Grep first:

```bash
grep -rl '<?[^p=x]' application/views | wc -l
```

## 6. Checklist

- [ ] PHP 8.1+ everywhere, including CLI and cron
- [ ] `php bin/ko8-doctor.php application` reports 0 will break
- [ ] `composer require ko8/ko8`
- [ ] `encrypt` module enabled, `MCRYPT_*` constants replaced
- [ ] `URL::site()` / `URL::base()` overrides updated
- [ ] `count()` on possibly-null variables guarded
- [ ] Implicit nullable parameters made explicit (`?array $x = NULL`)
- [ ] Dynamic properties declared or `#[\AllowDynamicProperties]`
- [ ] `$magic_quotes` shim added or removed
- [ ] `Cookie::$salt` set
- [ ] `application/config/url.php` with `trusted_hosts`
- [ ] Error handler guards `Kohana::$log`
- [ ] Legacy ciphertext migrated or shimmed
- [ ] `application/cache` and `application/logs` writable
- [ ] Test suite runs: `composer test` and `composer test:legacy`

## 7. Finding the rest

The doctor is a scanner, not a proof. For what it cannot see:

```bash
vendor/bin/rector process application --dry-run
composer test          # does your suite still pass?
```

And the error page tells you exactly which file and line failed. It inlines its own CSS, so it renders even when your document root or rewrite rules are the problem.

## 8. Reported behaviour changes

Nothing in ko8 changes behaviour on purpose. The items above are PHP 8 requirements, not design decisions. If you find a real regression, it is a bug: open an issue with the smallest application that reproduces it.

The `VERSION` change is the one deliberate difference, and it is a string, not behaviour. `Kohana::KOSEVEN_COMPAT` is what code should compare against.
