# ko8 – Kohana compatible with PHP 8

Hello,

Short version: Kohana runs on PHP 8 again. It is called **ko8**.

Long version, because this is the sixth post in a series I did not plan.

I started using Kohana in 2011, at TuManitas. JesusDiez recommended it after we
evaluated a few frameworks, and he was right. Small footprint, fast to learn,
modules (ORM, Image, Auth), HMVC, good community. What else do you need.

At the end of 2011 we announced [Open Classifieds 2.0 on Kohana](https://garridodiaz.com/help-wanted-open-classifieds-2-0/).
It took months. Kohana 3.3 came out in 2012 with PSR-0, I was too deep in the
migration to move again, so OC stayed on 3.2.3. That was a mistake, but a cheap
one at the time.

Then [Kohana died](https://garridodiaz.com/kohana-kohana/). Final releases in
2014, forum down for weeks, the core developers did not believe in MVC anymore
and did not use the framework. Fair enough. I moved OC to 3.3.x anyway — [a bit
messy](https://garridodiaz.com/update-kohana-3-2-x-3-3-x/) but it worked.

In 2015 PHP 7 was coming and Kohana was not going to support it. I spent a month
[checking compatibility](https://garridodiaz.com/upgrading-kohana-3-3-x-to-php-7/),
sent a couple of patches that never got merged, and published the fixes as
gists. One file for `Exception.php`, one for pagination, one for APCu. That was
all PHP 7 needed.

So I put those fixes in a fork and called it
[Koseven](https://garridodiaz.com/koseven/), and announced it in January 2017.
I said it back then: *nothing new, nothing fancy, just small stuff. And yes, YAF
(yet another fork).* It held for nine years. In April 2026 it was archived too,
with a last commit that says "docs: project archived — thank you everyone".

Same place as 2015, but a few PHP versions later. `each()` and `money_format()`
were gone, `strftime()` and `utf8_decode()` deprecated, `count(null)` an
exception, and `mysqli` throws instead of returning `FALSE`. The application
still works, the platform underneath it does not.

So I did it again. **ko8** is the Koseven 3.3.9 API with the PHP 8 problems
fixed, and nothing else. Same classes, same `Route::set()`, same
`ORM::factory()`, same cascading filesystem. Copy `system/` and `modules/`,
keep `application/`, run the doctor, fix the two or three lines it tells you
about.

![The ko8 welcome page](screenshots/01-welcome.png)

I tested it on Open eShop, which is the only test I trust. A classifieds
platform from 2013. Copy the framework, run the doctor, fix one `money_format()`
call and one implicit nullable. That is it. Home, listing, product, search,
login, contact, RSS, panel: all 200, no deprecations, empty error log, on PHP
8.5.

![Open eShop running on ko8](screenshots/07-openeshop.png)

The framework's own pages were old HTML, so the error page, the installer, the
profiler, pagination and the docs are Tailwind now. Not because production cares,
but because the first page a developer sees should not look abandoned.

![The guide and API browser](screenshots/02-guide.png)

1,484 tests, most of them inherited from Kohana and Koseven and ported
mechanically. CI runs on PHP 8.1 to 8.5. If a link in the guide is broken, the
build fails.

And the honest part, again: **I am not going to maintain ko8.** I am not running
it in production. I did it because I wanted to know if a framework I started
using in 2011 could still run on a PHP from 2026, and how much of the boring part
an AI could do without breaking it. Both answers are yes, with caveats. The code
is BSD-3-Clause. Fork it, rename it, publish it, sell support for it. You do not
need to ask.

Links:

- Repository: [github.com/neo22s/ko8](https://github.com/neo22s/ko8)
- Release: [ko8 8.0.0 (genesis)](https://github.com/neo22s/ko8/releases/tag/v8.0.0)
- Migration guide: [docs/migration.md](https://github.com/neo22s/ko8/blob/ko8/docs/migration.md)

regards
