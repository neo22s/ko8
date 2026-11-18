# YouTube script — "Ko8: can a 2010 PHP framework survive 2026 (and can an AI do it?)"

Target length: 12–15 minutes. Language: English. Tone: first person, unhurried,
no hype — you are showing something you built and then giving away.

The script is written as spoken lines, not bullet points. Bracketed text is a
stage direction (what is on screen), not something to read.

---

## 0. Cold open — 0:00

[Screen: the ko8 error page on screen, then the welcome page. No logo, no music
yet. Just the page and your voice.]

**You:**

> This is Kohana. Or it is what is left of it.
>
> It is a PHP framework from 2008. It was declared dead in 2017. The fork that
> kept it alive was archived in April 2026. And this — [pause] — this is it
> running on PHP 8.5, inside a classifieds platform that has been in production
> since 2013, without rewriting a single line of its business logic.
>
> I want to show you how, and I want to be honest about what happens to it next.

[Title card: **ko8 — Kohana on PHP 8. Not maintained. Fork it.**]

---

## 1. What Kohana was — 0:40

[Screen: the guide page, /guide/kohana. Scroll slowly through the menu on the
left — Conventions, Cascading Filesystem, Config, Routing.]

**You:**

> If you have never used Kohana, here is the one-minute version, because its
> design decisions are why this video is possible at all.
>
> Kohana was a small MVC framework. The idea was that the framework should get
> out of the way: no configuration files to memorise, no code generation, no
> application container. And the thing that made it special was the **cascading
> filesystem**.
>
> Everything — a class, a config file, a view, a translation, even the
> documentation — is found by walking from your application folder down through
> the modules to the system folder. Want to override something? Drop a file in
> the right folder with the right name. That is it. No registration, no service
> provider, no "magic".
>
> It was a genuinely good idea, and a lot of production code still depends on it.

[Screen: open eShop home page.]

**You:**

> This is open eShop. Classifieds and e-commerce. It started on Kohana 3.3. I
> have been shipping it since 2013. It is the reason the next part of this story
> exists.

---

## 2. Death, and the first fork — 2:00

[Screen: GitHub, koseven/koseven — the archived banner.]

**You:**

> PHP kept moving. By 2016 the Kohana team had stopped, and the framework did not
> run cleanly on PHP 7. So a few of us forked it. That fork was **Koseven** —
> sometimes called ko7, Kohana for PHP 7.
>
> [Point at the repository] It was not a rewrite. It was the same framework with
> the PHP 7 problems fixed. That is a very specific kind of project, and it is
> less glamorous than it sounds and more useful than it sounds.
>
> Koseven held for eight years. Then, April 2026:

[Zoom into the last commit message: "docs: project archived — thank you
everyone".]

**You:**

> That is the last commit. And fair enough — nobody owes anyone maintenance forever.
>
> But it leaves every application that depends on it in the exact position
> Koseven was in 2016, one PHP version later. PHP 8 removed `mcrypt`. It removed
> `each()`. It removed `money_format()`. It turned `count(null)` from a warning
> into an exception, and `array_key_exists()` on an object into a fatal.
>
> The application still works. The platform underneath it does not.

---

## 3. The question — 3:15

[Screen: return to the ko8 welcome page.]

**You:**

> So I had two questions, and honestly only one of them is about PHP.
>
> First: **can a framework from 2010 actually still run on a PHP from 2026?**
> Not "in theory", not "with a compatibility layer" — actually run, a real
> application, tests passing.
>
> Second: **can an AI do the tedious part of that without breaking it?**
>
> I built ko8 to find out. It is the Koseven 3.3.9 API with the PHP 8 problems
> fixed, and nothing else.

[Screen: terminal, `php bin/ko8-doctor.php` running. Let it scroll.]

**You:**

> The first thing I built was not the framework. It was the thing that tells you
> whether you need the framework.

---

## 4. The doctor — 4:00

[Screen: the doctor output, on open eShop.]

**You:**

> This is `ko8-doctor`. You point it at an application and it reads it — it does
> not execute it — and it tells you what will break, with the file, the line, and
> the fix.
>
> Nine hundred and thirty-nine files. One real break: a stray `money_format()`
> call, which PHP removed in version 8. Thirteen implicit nullables — `array $x
> = NULL`, deprecated in 8.4, fix is one character. And a list of things to check.

[Screen: zoom on the output.]

**You:**

> Now, here is the interesting part, and the reason this tool is not a `grep`.
>
> It is token based. Not regex.
>
> `$this->money_format()` — that is the application's own method. Not the
> built-in. `function money_format()` is a declaration, not a call. And this:
> [show a JS template string with `$('.x').each(`] — that is jQuery inside a PHP
> string. A regex flags all three. The tokenizer flags none.
>
> My first version got this wrong, and flagged a hundred and forty-seven things
> in open eShop. A hundred and forty-six of them were noise. A report you ignore
> is worse than no report, because it teaches you to ignore the next one too.

---

## 5. The migration, live — 5:30

[Screen: terminal in open eShop. Show `oc/kohana/system` version.]

**You:**

> So let's do it. This is the actual migration, on the actual application.
>
> Step one: copy ko8's `system` and `modules` over the existing ones. That is it.
> The application folder does not move.

[Screen: the `cp -R` command, then `diff -rq`.]

**You:**

> Step two: run the doctor. It reports one break and thirteen warnings.
>
> Step three: fix them. This one is a `money_format()` call — PHP removed it, so
> it becomes `NumberFormatter`. This one is an implicit nullable, so it gets a
> question mark.

[Screen: the two edits, quickly, no dead air.]

**You:**

> Step four: reload.

[Screen: the open eShop home page loads. Then the product page. Then search.]

**You:**

> That is it. Home, listing, product, search, login, register, contact, RSS,
> sitemap, admin panel. HTTP 200, no fatals, no deprecations, empty error log. On
> PHP 8.5.
>
> Thirteen years of classifieds platform, and the application code I changed is
> two lines.

[Screen: `diff -rq` showing no differences between the app's system folder and
ko8's.]

**You:**

> And the framework folder is byte-for-byte identical to what is in the
> repository. No local patches. That is the whole point.

---

## 6. The unglamorous part — 7:00

[Screen: the error page.]

**You:**

> Now, the part that took the longest, and it is not the part you would guess.
>
> Kohana's error page, its installer, its profiler — 2010 HTML. And that matters
> more than it sounds, because when the first thing a developer sees is a page
> that looks broken, they conclude the framework is broken.

[Screen: scroll through the error page — source, stack trace, environment.]

**You:**

> So everything the framework serves itself is Tailwind now. The error page, the
> installer, the profiler, pagination, the benchmark tool, the guide, and the API
> browser.
>
> This page inlines its own CSS. That is deliberate: it is the page that appears
> when everything else is wrong, so it has to render even if your document root
> or your rewrite rules are the problem.

[Screen: the API browser, /guide-api/Kohana_HTTP_Exception_404.]

**You:**

> And this is the API browser. It is reflection over every loaded class, so it is
> the cheapest possible test suite: if a class renders here, it compiles cleanly
> on PHP 8.5.

---

## 7. The bug that taught me the most — 8:30

[Screen: the guide page, then a link into /guide-api.]

**You:**

> One bug is worth its own minute, because it is the most instructive thing that
> happened.
>
> Every link in the documentation was relative. On a guide page that is fine. But
> the same documentation is also rendered inside the API browser, at
> `/guide-api/ClassName` — and there, a link written as `guide-api/Foo` resolves
> against the current directory. So it becomes `/guide-api/guide-api/Foo`. Four
> oh four.
>
> It had been like that for years. Nobody noticed, because nobody clicks every
> link in the API browser.
>
> The fix was a single resolver that turns every link into an absolute path, and
> degrades to plain text when the target does not exist. And then two tools: one
> that checks the Markdown on disk, one that crawls the rendered site the way a
> browser resolves URLs.

[Screen: the two tools running.]

**You:**

> Four hundred and one pages, zero broken links. And a test that fails if a
> single relative `href` appears on a page, so it cannot come back.
>
> The lesson is not "AI fixed the links". The lesson is that I had guessed the
> guide's convention instead of reading it, and it took a crawler to tell me.

---

## 8. What the AI did, and what it did not — 10:00

[Screen: your editor, a diff of something mechanical, e.g. the nullable params.]

**You:**

> Let me answer the second question honestly, because it is the reason I made
> this video and not just a repository.
>
> The mechanical part, the AI is genuinely good at. Ninety implicitly nullable
> parameters. `E_STRICT` removed. `strftime` gone. `utf8_decode` deprecated.
> `mysqli` throwing exceptions where the driver expected `false`. ORM models
> implementing `Serializable`. That is a long, boring, error-prone list, and it
> is exactly the work a machine does better than I do at one in the morning.

[Screen: the CI going red, then green.]

**You:**

> But it is confidently wrong in ways that look right. The first version of the
> doctor looked fine and silently missed three of its five rules, because it
> compared token constants to strings. My first version of the link resolver
> broke forty links, because I had guessed a convention. The first CI run went
> red for a reason that does not reproduce on a Mac.
>
> None of that was caught by the model knowing more. It was caught by running
> things, on a second machine, on a PHP version I was not using.

[Screen: the CI matrix, all green.]

**You:**

> So the honest summary is: the bottleneck moved. Writing eighteen thousand lines
> of compatible PHP is no longer the expensive part. Knowing which eighteen
> thousand lines to check, and refusing to believe a green test that tests the
> wrong thing — that is still the job.

---

## 9. It is not maintained. Take it. — 11:30

[Screen: the README, scrolled to the maintenance section.]

**You:**

> And now the part I want to be very clear about, because most projects bury it.
>
> **I am not going to maintain ko8.**
>
> I am not running it in production. open eShop works on it, but open eShop is not
> my product any more. I built this because I wanted to know whether it could be
> done, and now I know.

[Screen: the licence file, BSD-3-Clause.]

**You:**

> That is not a warning, it is a handover. The licence is BSD. Fork it, rename it,
> publish your own package, sell support for it. You do not need my permission,
> and you do not need to ask.

[Screen: three rules on screen as text.]

**You:**

> If you do take it, three rules keep it useful.
>
> One: never rename the `Kohana_` classes. That prefix is what the autoloader
> looks for. Renaming it breaks every application for no functional gain. It is
> the one thing that would make all of this worthless.
>
> Two: never add a parameter to a method people override. Koseven added one
> parameter to `URL::site()`, and that single change was the most common fatal
> when migrating a real application. Once was enough.
>
> Three: keep the tests. All fourteen hundred and eighty-four of them. They are
> the reason this is a framework and not a demo.

[Screen: ROADMAP.md.]

**You:**

> The roadmap has the security work I would do first: `SameSite` cookies, refusing
> to unserialize unencrypted session payloads, `password_hash` in the auth
> driver. None of it needs an API change, which means it is all doable by someone
> who is not me.

---

## 10. Close — 13:00

[Screen: the guide page, then the repository.]

**You:**

> Kohana died in 2017. Koseven kept it alive until 2026. ko8 gets it onto PHP 8,
> tested, documented, on a real application.

[Pause. Let the page sit.]

**You:**

> If somebody picks it up, good. If nobody does, the code is here, it works, and
> that is a better ending than a dead repository.
>
> Links are below. Fork it.

[End card: repository URL, release URL, migration guide. Music out.]

---

## Production notes

**Screenshots to capture** (all in `docs/screenshots/`):

| Shot | Used in |
|---|---|
| `01-welcome.png` | cold open, section 3 |
| `02-guide.png` | section 1, 7 |
| `03-api-browser.png` | section 6 |
| `04-api-class.png` | section 6 |
| `05-error-page.png` | cold open, section 6 |
| `06-installer.png` | section 6 (optional) |
| `07-openeshop.png` | section 1, 5 |

**Terminal recordings to make:**

1. `php bin/ko8-doctor.php application` — on open eShop, full output.
2. The migration: `cp -R`, `diff -rq`, the two edits, the reload.
3. `php tools/check-guide-links.php` and `php tools/crawl-docs.php`.
4. `composer test`, `composer test:legacy`, `composer smoke`.
5. The CI matrix, green, scrolling once.

**Things to have open in tabs:** the repository, the release, the guide on
`koseven.test`, open eShop on `openeshop.test`, the CI run.

**Do not:**

- Narrate the AI as a protagonist. It is a tool with a failure mode, and the
  failure mode is the interesting part.
- Say "modernise". Nothing here is modern; that is the feature.
- Apologise for the lack of maintenance. State it once, clearly, and move on —
  the handover is the point, not a caveat.
