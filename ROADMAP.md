# Roadmap

ko8 exists for one reason: Kohana/Koseven applications should run on a supported
PHP without being rewritten. Everything below serves that, and nothing below is
allowed to break the Koseven 3.3 API.

## The rules

1. **The API does not change.** No renames, no removed methods, no new required
   parameters on anything an application can override. `URL::site()` gained
   `$subdomain` in Koseven and that single change was the most common fatal when
   migrating a real application; it will not happen again.
2. **Compatibility over purity.** A class that is ugly but works stays until
   there is a reason to change it. This is not a rewrite of Kohana.
3. **Small.** The core is ~17k lines. It should stay readable in an afternoon.
4. **No new dependencies in the core.** Composer, PHPUnit and Tailwind are build
   tools, not runtime requirements.

## 8.0 `genesis` — current

- [x] PHP 8.1 – 8.5 clean. No deprecations, no removed functions.
- [x] `Kohana::VERSION` = `8.0.0`, `Kohana::KOSEVEN_COMPAT` = `3.3.9`.
- [x] All framework UI on Tailwind: error page, installer, profiler, pagination,
      codebench, the guide and the API browser.
- [x] New test system: PHPUnit 11 unit suite, the inherited 1441-test suite, and
      a smoke test that boots, routes and renders on every supported PHP.
- [x] Documentation rebuilt: 106 guide pages, 173 API pages, zero broken links,
      checked by two tools and a test.
- [x] `bin/ko8-doctor.php`: reports what in an application will not run on PHP 8.
- [x] `openssl` is the default encryption engine; `mcrypt` is a tombstone.

## 8.1 — hardening

Security work that does not change the API but does change behaviour, so it is
opt-in where it has to be:

- [ ] `Cookie`: `SameSite` support (`Cookie::$samesite`), `httponly` on by
      default, and HMAC-SHA256 instead of SHA1. Old cookies keep verifying.
- [ ] `Session`: refuse to unserialize an unencrypted cookie payload unless the
      application opts in. `unserialize()` of user-controlled data is the one
      remaining sharp edge in the default configuration.
- [ ] `Auth`: `password_hash()` / `password_verify()` alongside the existing HMAC
      drivers, with a rehash-on-login path. `Auth_Bcrypt` already exists in ORM;
      it needs to be the documented default.
- [ ] Security headers helper (`Response::security_headers()`) for CSP, HSTS and
      `X-Content-Type-Options`, off by default.

## Later

- [ ] PHP 8.6 / 9.0 readiness as they land.
- [ ] `system/src/` for genuinely new code, if new code is ever needed. It is
      empty on purpose: a namespace with nothing in it is a promise nobody keeps.
- [ ] Optional PSR-3 logger bridge. Optional, because `Log` works and is 200
      lines.
- [ ] Coverage in CI once the legacy suite runs without skips.

## Not planned

- A DI container, annotations, a queue, an event bus.
- Renaming `Kohana_*`. It would break every application for no functional gain.
- A new template engine. `View` is fine.
- Dropping PHP 8.1 while 8.1 is in security support.

## Versioning

Semantic versioning from 8.0:

| Change | Version |
|---|---|
| A break in the Koseven 3.3 API | 9.0 (never, ideally) |
| New behaviour, opt-in | 8.x minor |
| Bug and security fixes | 8.x.y patch |

`Kohana::KOSEVEN_COMPAT` records which Koseven release the API matches. It only
changes if ko8 ever deliberately diverges, which would itself be a major version.
