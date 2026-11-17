# Migrating from Koseven

ko8 keeps the Koseven 3.3 API. Migration is not a rewrite: replace the framework and touch the handful of places where PHP 8 changed behaviour.

> **Before you start.** Run `public/install.php` first. It checks the environment in seconds and often explains the problem before you read a single log line.

## 1. Requirements

| | Koseven | ko8 |
|---|---|---|
| PHP | 7.0+ | **8.1+** |
| mbstring | recommended | **required** |
| Composer | optional | required |
| `mcrypt` | optional | **removed** |

## 2. Replace the framework

```bash
composer require ko8/ko8:^8.0 --with-all-dependencies
```

`ko8/ko8` declares `replace: koseven/koseven` and `kohana/core`, so packages that require either keep working.

If you vendor the framework by hand (the classic `system/` + `modules/` layout):

```bash
mv system system.old
mv modules modules.old
cp -R vendor/ko8/ko8/system .
cp -R vendor/ko8/ko8/modules .
```

Keep `application/` untouched. That is the point.

## 3. What ko8 fixes for you

These were **fatal or wrong** on PHP 8 and are now handled:

| Issue | Where |
|---|---|
| `array_key_exists()` on a `Config_Group` threw `TypeError` | `Arr::path()` |
| `mysqli` throws `mysqli_sql_exception` since 8.1, driver expected `FALSE` | `Database_MySQLi::connect()` |
| `ReturnTypeWillChange` notices on 4 classes | `Validation`, `HTTP_Header`, `Config_Group`, `Database_Result` |
| `E_STRICT` deprecated in 8.4 | `Kohana_Exception::$php_errors` |
| `strftime()` deprecated in 8.1 | `Date::months()` |
| `utf8_decode()` deprecated in 8.2 | `UTF8::strlen()` |
| 90 implicitly nullable parameters | Rector, whole codebase |
| `Kohana::globals()`, `Encrypt::instance()` removed upstream | no longer referenced |

You do not have to do anything for these.

## 4. What you must change

### 4.1 `encrypt` is a module

In Kohana 3.3.6 it lived in the core; in Koseven and ko8 it is `modules/encrypt`.

```php
// application/bootstrap.php
Kohana::modules([
    // ...
    'encrypt' => MODPATH.'encrypt',
]);
```

### 4.2 `mcrypt` is gone

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

### 4.3 `URL::site()` gained a fourth parameter

Koseven added `$subdomain` to `URL::site()` and `URL::base()`. If your application overrides either with the old signature, PHP 8 refuses to load the class:

```
Declaration of URL::site(...) must be compatible with Kohana_URL::site(...)
```

Add the parameter:

```php
public static function site($uri = '', $protocol = NULL, $index = TRUE, $subdomain = NULL)
```

This is the single most common fatal when moving a real app. **ko8 will never add parameters to overridable methods again** — that is rule number one.

### 4.4 `Kohana::$magic_quotes` is gone

Removed upstream because magic quotes died in PHP 5.4. Applications that still read it get `Access to undeclared static property`.

```php
// application/classes/Kohana.php
class Kohana extends Kohana_Core
{
    public static $magic_quotes = FALSE;   // compatibility shim
}
```

Or delete the check from your `sanitize()`.

### 4.5 `count()` on `null` is a `TypeError`

PHP 8 turned this warning into an exception. Audit views that assume a variable is always a collection:

```php
// before
<?php if (count($products)): ?>

// after
<?php if ($products AND count($products)): ?>
```

The usual culprit is a controller that leaves `$products = NULL` when a query returns nothing.

### 4.6 `Kohana_Exception::response()` may run before `$log` exists

If an error happens during bootstrap, `Kohana::$log` is still `NULL` and the error page dies with a second, unrelated fatal that hides the first one:

```php
// application/classes/Kohana/Exception.php
if (Kohana::$log)
{
    Kohana::$log->add(Log::ERROR, parent::text($e));
}
```

### 4.7 Declare your trusted hosts

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

### 4.8 Optional: `short_open_tag`

ko8's own views use full `<?php` tags. If your views use `<?`, either enable `short_open_tag` or convert them. Grep first:

```bash
grep -rl '<?[^p=x]' application/views | wc -l
```

## 5. Checklist

- [ ] PHP 8.1+ everywhere, including CLI and cron
- [ ] `composer require ko8/ko8`
- [ ] `encrypt` module enabled, `MCRYPT_*` constants replaced
- [ ] `URL::site()` / `URL::base()` overrides updated
- [ ] `count()` on possibly-null variables guarded
- [ ] `$magic_quotes` shim added or removed
- [ ] `Cookie::$salt` set
- [ ] `application/config/url.php` with `trusted_hosts`
- [ ] Error handler guards `Kohana::$log`
- [ ] Legacy ciphertext migrated or shimmed
- [ ] `application/cache` and `application/logs` writable
- [ ] Test suite runs: `composer test`

## 6. Finding the rest

Rector catches what grep cannot:

```bash
vendor/bin/rector process application --dry-run
```

And the error page tells you exactly which file and line failed. It inlines its own CSS, so it renders even when your document root or rewrite rules are the problem.

## 7. Reported behaviour changes

Nothing in ko8 changes behaviour on purpose. The items above are PHP 8 requirements, not design decisions. If you find a real regression, it is a bug: open an issue with the smallest application that reproduces it.
