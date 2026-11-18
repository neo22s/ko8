# Contributing

ko8 is a compatibility project. The most valuable contribution is not a feature:
it is a bug report from a real application that would not run.

## Before you write code

```bash
composer install
composer smoke     # does the framework boot on your PHP?
composer test      # ko8 suite
composer test:legacy
```

If `composer smoke` fails, that is the bug. It boots the framework, routes a
request, renders a guide page and the error page, and round-trips an encrypted
string, on whichever PHP you are running. Nothing else can be trusted until it
passes.

## The rules

1. **Never change the public API.** No renames, no removed methods, no new
   required parameters on anything an application can override
   (`URL::site()` is the cautionary tale). If a fix seems to need one, it does
   not: find another way.
2. **`Kohana_*` stays.** Every class keeps its name and its transparent
   extension. That is the whole point of the project.
3. **No new runtime dependencies.** Composer, PHPUnit and Tailwind are build
   tools. The framework requires PHP and mbstring, and nothing else.
4. **Every fix gets a test** in `tests/unit`, or a line in
   `tools/check-guide-links.php` if it is a documentation fix.
5. **Spanish is fine** in commit messages and code comments. The code itself,
   the docs and the guide are English.

## What to run before a commit

```bash
composer lint          # php -l over system, modules, application, bin, tools, public
composer smoke
composer test
composer test:legacy
composer check:guide   # every guide and docblock link resolves
```

All five must pass. `composer check:guide` takes a second and catches the class
of bug where a docblock link works on `/guide` and 404s on `/guide-api`.

## Changing the documentation

The guide lives next to the code, in `system/guide/<module>/` and
`modules/<module>/guide/<module>/`. Links are relative to the **module root**,
not to the page you are editing:

```markdown
[Configuration](config)              guide/<current module>/config
[Filesystem](../kohana/files)         guide/kohana/files
[Request](api/Request)                the API browser
```

A link to a page that does not exist is not an error at render time: it degrades
to plain text. `composer check:guide` is what tells you it happened.

## Changing the UI

All framework HTML is Tailwind. The compiled CSS is committed, so production
does not need Node:

```bash
npm install
npm run build     # writes system/media/css/ko8.css and the userguide copy
```

Do not hand-edit `system/media/css/ko8.css`; it is generated from
`assets/tailwind/ko8.css`. If you add a class in a view, add it to the Tailwind
sources first or it will not exist in the build.

## Reporting a bug

Include:

- PHP version (`php -v`), ko8 version (`Kohana::version()`).
- The smallest application that reproduces it, or the exact stack trace.
- The output of `php bin/ko8-doctor.php application`, if the application is one
  you are migrating.

If an application that ran on Koseven fails on ko8, that is a regression and it
takes priority over everything else in the roadmap.
