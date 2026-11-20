# Solo Hustle — CLAUDE.md

> Client answers confirmed 2026-11-20. Build locally first. The host (Namecheap or Heroku), domain, Brevo and Google come later.

## How we work (read first)
- **One sub-step per prompt.** Do only the sub-step Henry names (e.g. "B2").
- **After each sub-step, in this order**:
  1. Run Pint and the tests.
  2. Commit to `staging` and push.
  3. Tick the sub-step in **Progress** below, add its browser checks to `docs/QA.md` (same style: short checkbox steps), rebuild the client PDF with `php docs/build-qa-pdf.php`, and commit all three.
  4. Tell Henry, briefly, what changed and what to test in the browser.
  5. **Stop and wait for his OK.** Do not start the next sub-step on your own.
- **End of a group** (all of A, all of B, …): ask "Group X done. Merge `staging` into `main` and push?" Merge only on a yes, and use `--no-ff`.
- **Before deciding something not covered here**, ask Henry. Tell him about edge cases before building, not after.
- **Prefer the simplest solution that works.** No extra scope, no new packages without a reason.
- **Outstanding UI/UX** matters to Henry: polished, friendly screens for visitors, writers and the owner (clear empty states, helpful messages, sensible defaults).
- **Keep replies short.** No dumping files or restating code. Report: what changed, what to test, anything he must decide.

## Git
- Remote: `https://github.com/Moby47/solohustle.git` (origin).
- The fresh install is committed once on `main`. All work after that happens on `staging`. `main` only ever receives tested groups.
- Commit messages: short imperative subject, prefixed with the sub-step, e.g. `B2: writer panel registration and email verification`. Long sub-steps can have several commits.
- Never commit `.env`, `node_modules`, uploads or secrets.

## Code style
- Laravel conventions and Pint's default (Laravel) style. `declare(strict_types=1);` in new PHP files.
- Clear names. Small classes and methods. No clever one-liners.
- **Comments:**
  - A one-line docblock on each class saying what it is for.
  - Docblocks on public methods whose purpose isn't obvious from the name.
  - Inline comments only to explain *why* (a business rule, a hosting quirk). Never restate what the code does.
  - No comment banners or commented-out code.
- Use backed enums for statuses and types, Policies for permissions, and Form Requests or Filament validation for all input.
- Admin labels and helper text are in plain English. The owner is not technical.

## What this is
Solo Hustle ("Work. Write. Build.") is a career and creator hub with three parts:
- curated remote/online **jobs**, added by hand by the owner, each linking to the real employer page
- a **blog** of practical guides (Career, Freelancing, SEO & Content, Reviews & Guides)
- **guest writers** who publish original work as portfolio samples, after the owner reviews it

It must look credible and editorial. It must **not** look like a make-money-online blog or a generic job board.
Client brief: `docs/Solo_Hustle_Website_Build_Brief.pdf`.

**Guiding rule: give the owner as much control as possible from the dashboard, as simply as possible.**
- Any text, link, image or setting she might want to change goes in the dashboard, not in code.
- Every integration (email, newsletter, analytics) is optional. While it isn't set up, the site works and nothing breaks.

## Stack
- Laravel 13, PHP 8.3+ (`composer.json` → `config.platform.php` = `8.3` until the host is chosen), MySQL 8.
- Local:
  - Herd at `C:\Users\Henry\Herd\solohustle` → http://solohustle.test
  - DBngin MySQL on 127.0.0.1:3306, root, no password, DB `solohustle`; tests use DB `solohustle_test` (MySQL, never SQLite)
  - Windows / PowerShell. Use `where.exe`, not `where`.
- Public site: Blade + Livewire 4 + Tailwind v4 (Laravel's default Vite setup). Use **no starter kit**, because Filament handles all logins.
- Admin and writer areas: **Filament 5**, with two panels:
  - `/admin`: owner (role `admin`)
  - `/writer`: guest writers (role `writer`). Includes registration, email verification and password reset.
- Search: Laravel Scout, `database` driver, using MySQL FULLTEXT. It can be swapped for Meilisearch later with no code changes.
- Queue and cache: `database` driver.
- Images: Intervention Image v3. Convert uploads to WebP and make sized variants.
- Other packages: `spatie/laravel-honeypot`, `spatie/laravel-responsecache`. The sitemap is built in-house (`App\Support\SitemapBuilder`) because no `spatie/laravel-sitemap` release supports both PHP 8.3 and Laravel 13.
- Tests: Pest.

## Portability (must run on Namecheap shared *or* Heroku)
Everything host-specific is a `.env` switch. No code changes between hosts.
- **Uploads** go through a `media` disk, set by `MEDIA_DISK`:
  - `media_local`: `public/uploads`. No `storage:link` needed. Used locally and on Namecheap.
  - `media_s3`: an S3-compatible bucket (Cloudflare R2). Used on Heroku, because Heroku's disk is wiped on every restart.
  - Always build URLs with `Storage::disk(config('media.disk'))->url()`. Never hard-code paths.
- **Database**: MySQL everywhere. Support `DB_URL`, which Heroku's JawsDB provides as `JAWSDB_URL`; map it in `config/database.php`.
- **Queue**: `database` driver.
  - Namecheap: cron runs the scheduler, which runs `queue:work --stop-when-empty`.
  - Heroku: a `worker` dyno.
- **Scheduler**:
  - Namecheap: a cPanel cron calls `schedule:run` every minute.
  - Heroku: the worker dyno runs `schedule:work`, or Heroku Scheduler.
- **Public path**: `APP_PUBLIC_PATH` (Namecheap only, so `public_html` can be the web root). Empty everywhere else.
- **Logs**: `LOG_CHANNEL=errorlog` on Heroku.

## Brand
- Blue `#0750A1` (primary, anchor), yellow `#F5D522` (sparing highlights and accents only), lavender `#B99BD8` (secondary), white.
- Charcoal body text, pale blue-grey for section and card backgrounds.
- Clean editorial style: strong heading hierarchy, whitespace, simple cards, restrained borders. **One main CTA per page.** Design mobile-first.
- Self-host one variable font (Inter or similar) with `font-display: swap`. No Google Fonts CDN.
- The logo goes top-left in the header (links home) and again in the footer. It's uploaded in Settings so the owner can replace it.
  - The default file is `docs/logo.*`. If it's missing, use a text logo and remind Henry.
- Filament panels use the same primary colour and logo.

## Roles
| Role | Can |
|---|---|
| Visitor | Browse and search jobs and articles, subscribe, send contact messages. No account needed. |
| Writer | Anyone can register. Must verify email. Edit own profile. Create/edit/delete own posts only while `draft` or `changes_requested`. Submit for review. See status and review notes. **Cannot publish. Cannot edit a post once it's pending, published or rejected.** To change a live post, they contact the owner. |
| Admin (owner) | Everything: jobs, posts (own and guest), reviews, categories, pages, menus, homepage, settings, writers, messages, subscribers. |

Writer registration is protected by honeypot, throttling and required email verification. The admin can suspend a writer, which blocks login and hides their author page.

## Owner control (all in `/admin`)
- **Dashboard widgets**:
  - submissions awaiting review
  - jobs needing re-check
  - unread messages
  - new subscribers this week
  - quick links "New job" and "New article"
- **Content**:
  - posts, categories, tags
  - jobs, job categories, regions
  - pages (she can add new pages, not only edit the seeded ones)
- **Writers**: list, view posts, suspend/unsuspend.
- **Inbox**: contact messages, with read/unread status and a reply-by-email link.
- **Subscribers**: list, delete, export CSV.
- **Redirects**: from path → to path (301). Created automatically when a published slug changes, and also editable by hand.
- **Site settings** (one Filament page with tabs; plain-English helper text on each field):
  - *General*: site name, tagline, logo, favicon, public contact email (default `solohustlehub@gmail.com`), where contact-form messages go.
  - *Homepage*:
    - hero headline, sub-text, two button labels and links
    - every section can be shown/hidden, with editable titles
    - number of jobs and articles shown
    - featured resource block (title, text, image, link)
    - creator block text
  - *Navigation*: header menu items and footer link groups. Repeaters with label, link (internal page/category or URL) and order. The header CTA button gets its own label and link. In C5, add a "pick a page" link picker (pages, categories, job pages, or a custom address) so the owner never has to type a path.
  - *Social links*: repeater with platform (select) and URL. Seed test links (`https://x.com/solohustle_test`, `https://facebook.com/solohustle.test`, `https://linkedin.com/company/solohustle-test`, `https://instagram.com/solohustle.test`).
  - *SEO defaults*: default meta description, default share image, title suffix, "discourage search engines" switch (on until launch).
  - *Article extras*: mid-article CTA text and link, newsletter box text.
  - *AI & search engines*:
    - One allow/block switch per AI crawler: GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, Google-Extended, CCBot. **All are allowed by default.** `robots.txt` is built from these.
    - `/llms.txt` on/off (default on).
    - Bing Webmaster verification code.
    - IndexNow on/off (default off until launch). Turning it on generates the key and serves the key file automatically.
  - *Integrations*. All optional; leave a field empty and that feature is simply off:
    - **Email sending (Brevo SMTP)**: host, port, username, password, from address, from name, plus a "Send test email" button. While empty, use the `.env` mailer: `log` locally, `sendmail` on Namecheap.
    - **Newsletter (Brevo)**: API key and list ID, plus "Test connection" and "Sync subscribers to Brevo" buttons.
    - **Google Analytics**: GA4 Measurement ID. The script is output only when it's set and the app is in production, and only after the visitor accepts a slim, non-blocking cookie consent bar (Accept / Decline, choice remembered; the bar only appears when GA is set). (Henry, 2026-11-21)
    - **Google Search Console**: verification code. The meta tag is output only when set.
  - Secrets use encrypted casts and are never shown back in full.
- Settings are cached and the cache is cleared on save. A service provider applies the mail settings to `config('mail…')` at boot.

## Data model
- **users**: name, email, password, role (`admin`/`writer`), slug, bio, photo, headline, website_url, linkedin_url, x_url, email_verified_at, suspended_at
- **categories**: name, slug, description, seo_title, meta_description, sort. Seed: Career, Freelancing, SEO & Content, Reviews & Guides
- **tags**, **post_tag**
- **posts**: author_id, category_id, title, slug, excerpt, body (HTML), cover_image, cover_alt, status (`draft`, `pending`, `changes_requested`, `published`, `rejected`), review_note, reviewed_by, reviewed_at, submitted_at, published_at, content_updated_at, key_takeaway, quick_answer (nullable, ~40–60 words), faqs (JSON: question/answer, nullable), sources (JSON: title/url, nullable), is_featured, reading_minutes, seo_title, meta_description, owns_work_confirmed_at, originality_confirmed_at
- **job_categories**: name, slug, intro, seo_title, meta_description, sort, related_category_id (nullable). Seed: Content Writing, SEO, Customer Support, Design, Development, Marketing, Virtual Assistant
- **regions**: name, slug, sort, is_worldwide. Seed: Worldwide, Africa, Europe, North America, Latin America, Asia-Pacific, Middle East
- **jobs** (table `job_listings`, because the queue uses `jobs`): title, slug, company_name, company_url, company_logo (nullable), job_category_id, region_id, country_code (nullable), location_note, work_mode (`remote`/`hybrid`/`onsite`), employment_type (`full_time`/`part_time`/`contract`/`freelance`/`internship`), experience_level (`entry`/`mid`/`senior`/`any`), summary, requirements (one per line), salary_text (nullable), apply_url, source_name, source_url, posted_at, last_verified_at, closes_at (nullable), status (`open`/`closed`), closed_at, is_featured, seo_title, meta_description
- **pages**: title, slug, body, seo_title, meta_description, is_published. Seed: About, Contact, Editorial Policy, Privacy Policy, Terms, Creator Guidelines, Advertise
- **contact_messages**: name, email, subject, message, read_at
- **subscribers**: email (unique), name (nullable), source, confirmed_at (nullable), brevo_synced_at (nullable), unsubscribed_at. Always stored locally; pushed to Brevo (queued) when it's configured. **Double opt-in**: a confirmation email must be clicked before a subscriber is active or sent to Brevo. (Henry, 2026-11-21)
- **redirects**: from_path (unique), to_path, hits
- **settings**: key/value (JSON), cached

Index slugs, status, published_at, posted_at, and the FK + status combos used by filters.

## Rules
- **Jobs**
  - Summaries are written by the owner. Never import or scrape job descriptions.
  - `apply_url` is required and must be an http(s) URL.
  - **Closing**:
    - If `closes_at` is set, a daily command closes the job once that date passes.
    - If there's no closing date, the job stays open until the owner closes it.
    - The owner can close or reopen any job at any time.
  - **Re-check**: the "Needs re-check" filter and dashboard widget show open jobs whose `last_verified_at` is more than 30 days old. A "Mark verified" action sets it to now. New jobs start verified now.
  - **Closed jobs** stay viewable with a "Closed" banner and no apply button. They are excluded from listings by default and set to `noindex`.
  - **Region filter**: filtering by a region also shows jobs in regions marked "open to applicants anywhere" (Worldwide). (Henry, 2026-11-21)
  - **Jobs ↔ guides**: each job category has an optional "Related blog category". Job pages show its latest guides; articles in that blog category link to those jobs. (Henry, 2026-11-21)
  - **Country**: optional ISO country on each job, used only for JobPosting structured data. (Henry, 2026-11-21)
- **Posts**
  - Submitting requires both confirmation checkboxes (owns the work / NDA ok, original). Save the timestamps.
  - Review actions:
    - Accept: publish now
    - Accept with edits: the admin edits, then publishes
    - Ask for changes: requires a note; the writer can edit again
    - Reject: requires a note; final
  - Each review action queues an email to the writer. Submissions queue an email to the admin. If mail fails, log the error and carry on.
  - Only `published` posts are public. The writer edit rules are enforced in the Post Policy, not just hidden in the UI.
  - When a published slug changes, a redirect is created automatically.
  - TOC: generated from H2/H3 in the body. Shown when there are 3 or more headings.
- **Author pages** exist only for non-suspended users with at least one published post.
- **Public forms** (contact, newsletter, writer register): honeypot plus `throttle`. Never trust the client for status or role.
- No ads, pop-ups or paid placements in v1. Later paid items must be visibly labelled "Sponsored".

## Screens (public)
- **Home**, in this order (each section can be toggled and retitled in Settings):
  1. hero
  2. job search block + latest jobs
  3. featured guides
  4. creator publishing block
  5. latest articles
  6. featured resource
  7. newsletter
- **Jobs** `/jobs`: one Livewire component with keyword, category, work mode, region, type, experience and date-posted filters. Filter state is kept in the URL query string (`#[Url]`), so filtered pages are shareable and crawlable, and links still work without JS. Paginated. Jobs show as cards on mobile. Show the result count and a "Clear filters" button.
- **Job landing pages**: `/jobs/remote` and `/jobs/{job-category-slug}`. Same component, prefiltered, with their own H1, intro text and meta.
- **Job detail** `/jobs/{category}/{slug}`: summary, requirements, facts, "Last checked {date}", source, Apply button (`rel="nofollow noopener"`, new tab), related jobs, related guides. JobPosting JSON-LD only while the job is open.
- **Blog**: category pages `/{category-slug}`, article `/{category-slug}/{post-slug}`.
  - Article template: breadcrumb, category label, H1, summary, byline + published/updated dates, TOC, body, mid-article CTA (after the 2nd H2), key takeaway, related articles, relevant job category link, newsletter.
  - Article JSON-LD and BreadcrumbList.
- **Author** `/authors/{slug}`. **Creators / Publish** `/publish`: benefits, rules, process, CTA to the writer signup.
- **Search** `/search?q=`: jobs + articles.
- **Pages** `/{page-slug}`. Put fixed routes first, and reserve slugs (jobs, authors, search, publish, admin, writer, uploads, build) so pages and categories can't take them.
- **Header and footer** are built from the Navigation settings. On mobile, the header uses a slide-out menu.

## SEO and speed
- One `<x-seo>` component per page: title, meta description (falls back to the excerpt or summary, then the default), canonical, Open Graph and Twitter tags, robots (respects the "discourage search engines" switch).
- Daily sitemap, plus a dynamic `robots.txt` route.
- Server-rendered HTML for all content. Livewire only where interactive.
- Full-page response cache for guests on article, job, page and home routes. Clear it when related models or settings are saved.
- Images: WebP, explicit width and height, `loading="lazy"` except the hero, `srcset`. Alt text is required in the admin.
- Eager-load relations (enable `Model::preventLazyLoading()` outside production).
- Target Lighthouse mobile ≥ 90 on home, jobs list, job detail and article.

## AEO / GEO (answer and AI engines)
Goal: make it easy for Google AI Overviews, ChatGPT search, Perplexity and Copilot to understand, quote and cite the site.
- **Article page**:
  - Quick answer: shown under the H1 in a subtle box, only when filled.
  - FAQs: shown near the end, before related articles, with FAQPage JSON-LD.
  - Sources: shown as a numbered "Sources" list, with links set to `rel="noopener"`.
- **Editor helper text, plus a short "Writing for search and AI" section on the Creator Guidelines page**: phrase headings as questions where natural, answer straight away, keep paragraphs short, use lists and tables, give real examples and cite sources.
- **Structured data**:
  - `Organization` (name, logo, URL, `sameAs` = social links from settings) and `WebSite` with `SearchAction` (`/search?q=`), in the layout.
  - `Person` on author pages (`sameAs` = their links).
  - `Article` includes `author` (Person URL), `datePublished` and `dateModified`. `dateModified` must match the visible "Updated" date.
  - `JobPosting` (D4) and `BreadcrumbList` are as already planned.
- **`robots.txt`**: a dynamic route built from the AI crawler switches and the "discourage search engines" switch.
- **`/llms.txt`**: a dynamic route listing the site name and description, then markdown links to the key pages, categories, job landing pages and the ~20 most recent articles. Cached and cleared on publish.
- **IndexNow**: when an article or job is published, updated or closed, a queued job pings `api.indexnow.org` with the URL. Only when it's enabled and in production.
- **Bing**: output the verification meta tag when it's set.
- Everything stays server-rendered HTML, so no content depends on JavaScript.

## Demo content
- `EssentialsSeeder` (used live): admin, categories, job categories, regions, pages, default settings, test social links.
- `DemoSeeder` (local only):
  - 1 admin (`admin@solohustle.test` / `password`)
  - 4 writers
  - ~16 lorem posts across all categories and statuses
  - ~30 lorem jobs (some with closing dates, some closed, some not verified for over 30 days)
  - a few subscribers and messages
  - locally generated branded placeholder images. Never hotlink.

## Deploy (host decided at H1)
- **Both hosts**: run `npm run build`. Production `.env` sets `APP_ENV=production` and `APP_DEBUG=false`. HTTPS is forced in production.
- **Namecheap shared (no SSH)**:
  - Build locally: `composer install --no-dev -o` and `npm run build`, then zip without `node_modules`, `.env` and tests (`deploy/build.ps1`).
  - Upload with cPanel File Manager: the app goes in `~/solohustle`, and the contents of `public/` go into `~/public_html`. Set `APP_PUBLIC_PATH`; `public_html/index.php` requires `../solohustle/...`.
  - Admin **System → Maintenance** page with buttons for:
    - Run migrations
    - Clear and rebuild caches
    - Rebuild sitemap
    - Rebuild search index
    - Last cron run time
  - First install through `/admin/install` (migrate + essentials seed, then it disables itself).
  - cPanel cron every minute: `/usr/local/bin/php /home/USER/solohustle/artisan schedule:run >/dev/null 2>&1`. Confirm the PHP path in cPanel.
- **Heroku**:
  - PHP buildpack plus Node for assets; deploy with `git push heroku main`.
  - `Procfile`:
    - `web: vendor/bin/heroku-php-apache2 public/`
    - `worker: php artisan schedule:work` (also runs the queue via the scheduler)
    - `release: php artisan migrate --force && php artisan optimize`
  - JawsDB MySQL, `MEDIA_DISK=media_s3` with R2 credentials, `LOG_CHANNEL=errorlog`.
- **Scheduler tasks** (both hosts):
  - `queue:work --stop-when-empty --max-time=50` every minute (withoutOverlapping)
  - close expired jobs daily
  - sitemap daily
  - `queue:prune-failed` weekly
  - record the last-run timestamp

## Build order and Progress
Tick each sub-step (`[x]`) when it's done and committed. At the end of each group, ask to merge into `main`.

**A. Foundation**
- [x] A1. Create the project:
  - `composer create-project laravel/laravel .` in the empty folder (not `laravel new`, which refuses an existing folder).
  - Pest, git init, first commit on `main`, add origin, push, create `staging`.
  - `.env` for DBngin, `config.platform.php` = 8.3, migrate.
- [x] A2. Brand: Tailwind tokens, self-hosted font, base layout with header/footer shells, logo.
- [x] A3. Portability: `media` disks and `MEDIA_DISK`, `DB_URL`/`JAWSDB_URL`, `Procfile`, `APP_PUBLIC_PATH` hook.

**B. Accounts and admin**
- [x] B1. Filament 5, `/admin` panel, role column, admin access, brand colours.
- [x] B2. `/writer` panel: registration, email verification, password reset, profile, suspension check.
- [x] B3. Settings store and Settings page (all tabs except *AI & search engines*, which comes in F2), mail config from settings, "Send test email".
- [x] B4. Pages (edit/add, public route), Redirects (resource + middleware), `EssentialsSeeder`.

**C. Blog**
- [x] C1. Categories, tags, posts: models, enums, Policy, admin resources, editor, WebP covers, SEO fields, quick answer / FAQs / sources fields with helper text.
- [x] C2. Review flow (accept / accept with edits / ask for changes / reject), queued emails, slug redirects.
- [x] C3. Writer side: own posts, edit rules, submit with the two confirmations, status and notes.
- [x] C4. Admin Writers resource with suspend/unsuspend.
- [x] C5. Public: article template (quick answer, FAQs, sources), category pages, author pages with Person data, `/publish`, header/footer from Navigation settings.

**D. Jobs**
- [x] D1. Job categories, regions, jobs: models, enums, admin resource, Duplicate / Mark verified / Close-Reopen actions.
- [x] D2. Auto-close command (scheduled), "Needs re-check" filter.
- [x] D3. Public directory (Livewire, URL-synced filters), landing pages.
- [x] D4. Job detail, JobPosting JSON-LD, closed-job handling.

**E. Homepage, search and forms**
- [x] E1. Scout database search on posts and jobs, header search, `/search`.
- [x] E2. Homepage sections, driven by settings.
- [x] E3. Contact form (store + email), Inbox resource.
- [x] E4. Newsletter: local subscribers, Brevo push/sync/test, CSV export.
- [x] E5. Dashboard widgets.

**F. SEO, AEO/GEO and speed**
- [x] F1. `<x-seo>`, sitemap, Organization/WebSite/Article/FAQ structured data, GA4 / Search Console / Bing output from settings.
- [x] F2. AEO/GEO: AI & search engines settings tab, dynamic `robots.txt` with AI crawler switches, `/llms.txt`, IndexNow.
- [ ] F3. Response cache + busting, image `srcset`, indexes, eager loading, Lighthouse pass.

**G. Content and QA**
- [x] G1. `DemoSeeder` with placeholder images; full local click-through.
- [ ] G2. Full test pass: every form, link, filter and apply link, on phone, tablet and desktop.

**H. Launch**
- [ ] H1. Choose the host and build its deploy kit (Namecheap Maintenance/install pages + `build.ps1`, or Heroku config), plus `DEPLOY.md`.
- [ ] H2. Go live: SSL, cron/worker, emails, uploads, Search Console + Bing Webmaster, sitemap submitted, IndexNow on, "discourage search engines" switched off.
- [ ] H3. Handover PDF for the owner (large text): jobs, articles, reviews, pages, menus, homepage, connecting Brevo and Google.

## Tests (key rules only)
Pest feature tests for:
- writer permissions (can't publish, can't edit pending/published/rejected posts)
- the auto-close command
- job filters
- public visibility (only published posts and open jobs listed)
- redirects
- newsletter signup when Brevo is not configured
- `robots.txt` respects the AI crawler switches
