# Solo Hustle — QA checklist

What to check after each sub-step. Work top to bottom, or jump to any section.
Tick a box when it passes. If something fails, note the step number and what you saw.

## Before you start

| What | Value |
|---|---|
| Public site | http://solohustle.test |
| Owner dashboard | http://solohustle.test/admin — `admin@solohustle.test` / `password` |
| Writer area | http://solohustle.test/writer — `writer@solohustle.test` / `password` |
| Emails (local) | Not sent. They are written to the end of `storage/logs/laravel.log`. Review emails are queued: run `php artisan queue:work --stop-when-empty` first to process them |
| Automated tests | `php artisan test` (should end in "passed") |

Tips:
- Use a private/incognito window for writer tests so your admin login doesn't get in the way.
- Check every page at phone width too (browser dev tools → device toolbar, or just narrow the window).

---

## A. Foundation

### A1. Project set-up
- [ ] http://solohustle.test loads.
- [ ] `php artisan test` passes.

### A2. Brand
- [ ] Text is in the Inter font (clean, modern sans-serif).
- [ ] Logo sits top-left in the header and again in the footer; both link to the homepage.
- [ ] There is one blue main button on the homepage.
- [ ] At phone width nothing spills off the side of the screen.
- [ ] Press Tab once on a fresh page: a "Skip to content" link appears.

### A3. Hosting switches
Nothing new to see.
- [ ] http://solohustle.test still loads.
- [ ] `php artisan test` passes.

---

## B. Accounts and admin

### B1. Owner dashboard
- [ ] http://solohustle.test/admin sends you to a login page.
- [ ] Log in as `admin@solohustle.test`: the dashboard is in the brand blue.
- [ ] Log out from the user menu (top right).

### B2. Writer area
In a private window:
- [ ] http://solohustle.test/writer/register: sign up with any email.
- [ ] You land on a "verify your email" screen.
- [ ] Open `storage/logs/laravel.log`, copy the verification link from the bottom into the browser: you reach the writer dashboard.
- [ ] User menu → **Profile**: add a photo, headline and links; save.
- [ ] Enter `javascript:alert(1)` as a link: it is refused.
- [ ] Log out, click **Forgot password?**, request a reset: the reset link appears in the log and works.
- [ ] A writer cannot open `/admin` (error page), and the admin cannot open `/writer`.
- [ ] `/admin/login` also has **Forgot password?**.

### B3. Site settings
Logged in as admin → **Site settings**:
- [ ] Change the site name and save: the browser tab title and the footer on the public site change.
- [ ] Upload a logo: it shows in the dashboard and in the public header and footer. Remove it: the text logo comes back.
- [ ] Upload a favicon: it shows in the browser tab.
- [ ] Navigation tab: drag menu items to reorder; add and remove a footer link group; save.
- [ ] Enter `javascript:x` as the header button link: refused with a plain-English message.
- [ ] Integrations: type an SMTP password and save: the field clears and says "Saved (ends in ••xx)".
- [ ] Save again with the password field empty: it still says "Saved".
- [ ] Click **Send test email**: it says the message went to the log file, and the message is at the end of `storage/logs/laravel.log`.
- [ ] Paste a whole Google meta tag into Search Console code and save: only the code is kept.
- [ ] Reload the page: the tab you were on stays open.

### B4. Pages, redirects and starter content
Public site (logged out):
- [ ] These pages open with real starter text: `/about`, `/contact`, `/editorial-policy`, `/creator-guidelines`, `/privacy-policy`, `/terms`, `/advertise`.
- [ ] Headings, lists and links in page text look neat and on-brand, including at phone width.
- [ ] A made-up address (e.g. `/nope`) shows a friendly "We couldn't find that page" screen with a way home.

Dashboard → **Content → Pages**:
- [ ] All seven pages are listed; search works; the "Published" filter works.
- [ ] **New page**: type a title and the web address fills itself in. Add some text with a heading, a list, a link and an image. Leave **Published** off and save.
- [ ] Click **Preview**: a yellow "Draft preview" bar shows at the top. In a private window the same address shows "not found".
- [ ] Turn **Published** on: the page is visible in the private window too.
- [ ] Try the web address `jobs` or `admin`: refused with a plain-English message.
- [ ] On a published page, change the web address and save. Open the old address: you land on the new one.
- [ ] Logged in as the owner, any page shows a small bar with **Edit in dashboard**.

Dashboard → **Settings → Redirects**:
- [ ] The redirect from the step above is listed, with **Times used** counting up as you visit the old address.
- [ ] **New redirect** from `/old-test` to `/about`; **Try it** opens /about.
- [ ] A redirect without a leading `/`, or from and to the same address, is refused.
- [ ] Redirects never override a page that exists.

---

## C. Blog

### C1. Articles, categories and tags (dashboard)
Dashboard → **Content**:
- [ ] **Categories** lists Career, Freelancing, SEO & Content, Reviews & Guides. Drag to reorder; the order sticks after reload.
- [ ] Add a category called `About`: refused, because a page already uses that address.
- [ ] A category that has articles can't be deleted (the delete button is greyed out with a hint).
- [ ] **Tags**: add, rename and delete a tag.
- [ ] **Articles → New article**: type a title and the web address fills itself in, with the category in the address preview.
- [ ] Write some text with headings, a list, a table and a link. Insert an image into the text.
- [ ] Add a tag straight from the article form (type a new name, then "Create").
- [ ] Upload a cover photo of any shape: it is cropped to a wide 16:9 frame; you can adjust the crop with the editor.
- [ ] Try to save with a cover but no image description: you're asked to describe the image.
- [ ] **Answer boxes**: type a quick answer and watch the word counter (green at 40–60 words).
- [ ] Add two FAQs and reorder them; add a source with a web link. A source link like `javascript:x` is refused.
- [ ] Set **Status** to Published and save: the publish date fills in automatically.
- [ ] Back in the list: the cover thumbnail, status badge and "Featured" star show; tabs (All, Awaiting review, Published, Drafts, Rejected) filter correctly; filters by category and author work.
- [ ] In the project folder, the cover is saved under `public/uploads/covers/` as three `.webp` files (full, 960 and 480 wide).

Writer area → **Profile**:
- [ ] Upload a photo of any shape: it is cropped square and saved as `.webp` under `public/uploads/authors/`.

### C2. Review flow
Setup: as the writer (private window, from C3 onwards you can do this in the writer area), or ask me to create a submitted test article. In the dashboard, an article "Awaiting review" shows a yellow count on **Articles** and a **Review** button in the list.
- [ ] Open a submission: a yellow box at the top says who submitted it and when, and that they confirmed it's their own original work.
- [ ] Buttons at the top: **Publish as is**, **Save my edits & publish**, and **Other decisions** (Ask for changes, Reject). Delete is hidden while it awaits review.
- [ ] **Ask for changes** without a note: refused. With a note: the article goes to "Changes requested" and the box shows your note.
- [ ] Submit again (as the writer), then **Reject** with a reason: status "Rejected".
- [ ] Submit another, edit its title, then **Save my edits & publish**: the new title is saved and it's published.
- [ ] Run `php artisan queue:work --stop-when-empty`, then check `storage/logs/laravel.log`: there's a friendly email for each step (new submission to you; published / changes requested / rejected to the writer), each with a button link.
- [ ] Change the web address of a published article: **Settings → Redirects** shows a redirect from the old address.
- [ ] Rename a category's web address: the category and each of its published articles get a redirect.

### C3. Writer articles
In a private window, log in at `/writer` as `writer@solohustle.test`:
- [ ] The dashboard shows three boxes: **In progress**, **With the editor**, **Published** (clicking one opens My articles).
- [ ] **My articles** with no articles shows a friendly empty state with **Write your first article**.
- [ ] **New article**: there is no status, author, featured or web-address field. Fill in a title, category and some text; save: "Draft saved" and you stay in the editor, with a "Your draft" note at the top.
- [ ] **Submit for review** with a very short text: an "Almost there" message explains what's missing (summary, at least 300 words).
- [ ] Write 300+ words and a summary; **Submit for review**: both checkboxes must be ticked, then you land on a read-only view saying "Awaiting review".
- [ ] The article can no longer be edited (no Edit button; opening the edit address directly is refused).
- [ ] As the owner, **Ask for changes** with a note: back as the writer, the article is editable again and the note shows in a blue box at the top and in the list.
- [ ] Resubmit; as the owner, **Publish**: the writer sees "Published" with a **View on site** button and can't edit it.
- [ ] A rejected article shows the editor's reason and can't be edited.
- [ ] Delete a draft: it's gone. Submitted articles have no Delete button.
- [ ] Another writer's articles never appear, even by changing the number in the address bar.

### C4. Writers (dashboard)
Dashboard → **People → Writers**:
- [ ] Every writer is listed with photo, name, email, published and total article counts, "Verified" tick, status badge and join date. The owner account is not listed.
- [ ] Search by name or email works; filter by Status (Active / Suspended) and Email verified.
- [ ] Open a writer: profile (photo, headline, bio, links), account details, and a list of their articles with **Review** / **Open** buttons that go to the article editor.
- [ ] **View author page** shows only when they have published articles (works from C5).
- [ ] **Suspend** a writer: a clear warning explains what happens; they show "Suspended".
- [ ] Try to sign in as that writer: "This account has been suspended…" message. Their author page (C5) shows "not found"; their published articles stay online.
- [ ] **Unsuspend**: they can sign in again.

### C5. Public blog, author pages, Publish page, header and footer
A sample article is in your local database: http://solohustle.test/career/find-your-first-remote-job

Header and footer (check at desktop **and** phone width):
- [ ] Header shows the logo, the menu from **Site settings → Navigation**, and the header button. The menu item for the section you're in is highlighted.
- [ ] At phone width, the menu icon opens a slide-out menu; it closes with the X, by tapping outside, or with Esc.
- [ ] Footer shows your link groups, the social links (as small labels) and the copyright line.
- [ ] **Site settings → Navigation**: the link fields are now dropdowns (Main sections, Blog categories, Pages). Use the **+** next to a dropdown to add any other address. Saved custom addresses still show.

Article page (the sample article):
- [ ] Breadcrumb, category label, title, summary, author photo/initials, published date and reading time.
- [ ] The **Quick answer** box sits under the title.
- [ ] Cover image (if set) is sharp and doesn't jump while loading.
- [ ] "In this article" table of contents: a sticky sidebar on desktop, a collapsible box on phones; links jump to each heading.
- [ ] The lavender mid-article box (from **Site settings → Article extras**) appears after the second section.
- [ ] Key takeaway (yellow), FAQs (click to open), numbered Sources, tags, "Written by" box, and "More in Career" at the bottom.
- [ ] Edit the article text in the dashboard on a later day: an "Updated" date appears next to Published.
- [ ] Logged in as owner you see "Edit in dashboard" at the top; logged out you don't.
- [ ] A draft opened by its writer shows a yellow "Preview" bar; logged out it shows "not found". A future publish date shows "Scheduled".
- [ ] In the dashboard, the article editor has **View on site** / **Preview** at the top.

Category pages:
- [ ] `/career` shows the intro text and article cards (newest first), with pages of 12.
- [ ] An empty category (e.g. `/freelancing`) shows "No articles here yet" with a button to Publish with us.

Author pages:
- [ ] Click the author's name on an article: photo, headline, bio, links and their articles.
- [ ] A writer with no published articles, or a suspended writer, has no author page (not found), and their name isn't a link.

Publish page:
- [ ] `/publish` shows the headline, intro, "Why write for us", numbered "How it works" steps, a link to the Creator Guidelines, and **Start writing** buttons that open the writer sign-up.
- [ ] Change the texts in **Site settings → Publish page**: the page updates.

---

## D. Jobs

### D1. Jobs, job categories and regions (dashboard)
Dashboard → **Jobs**:
- [ ] **Job categories** lists the 7 categories, each with its "Related guides" blog category. Drag to reorder. Try the web address `remote`: refused with an explanation.
- [ ] **Regions** lists 7 regions; only Worldwide has the "Anywhere" tick. Edit one to see the "Open to applicants anywhere" switch.
- [ ] **Jobs → New job**: fill in title, category, region, work mode / type / experience (click-buttons), summary, requirements (one per line), apply link, company and logo. Try the Country dropdown search (e.g. type "nig").
- [ ] An apply link like `javascript:x` is refused; the apply link is required.
- [ ] Save: the job is Open, posted today, and "Last checked" says today.
- [ ] Upload a wide logo: it is shrunk to fit (not cropped) and saved as `.webp` under `public/uploads/logos/`.
- [ ] In the list: logo, title, company · category, where (mode · region), status, posted, closes, last checked, featured star.
- [ ] Tabs: **Open** (default), **Needs re-check**, **Closed**, **All**.
- [ ] Row menu (⋮): **Mark verified** (last checked becomes "now"), **Close** (clear warning; status Closed), **Reopen**, **Duplicate** (opens an open copy called "… (copy)" dated today), **View on site** (works from D4).
- [ ] Select several jobs: bulk **Mark verified** and **Close** work.
- [ ] Open a job: the same actions are at the top of the editor.

### D2. Automatic closing and re-check reminders
- [ ] Create a job with a closing date of **yesterday**, one with **today**, and one with no date. Run `php artisan jobs:close-expired`: it says "Closed 1 expired job(s)"; only yesterday's job is now Closed.
- [ ] `php artisan schedule:list` shows `jobs:close-expired` running daily at 00:15.
- [ ] A job whose "Last checked" is over 30 days old (edit the date in the database, or wait) appears in the **Needs re-check** tab and filter, shows a warning icon in the list, and the **Jobs** menu item shows a yellow count.
- [ ] Reopening a job whose closing date has passed warns you to update the date first.

### D3. Public job directory
Eight sample jobs are in your local database (companies starting with "Sample", one of them closed).
- [ ] http://solohustle.test/jobs shows the headline and intro (from **Site settings → Jobs pages**), category buttons (Remote jobs + each job category), a search box, filters, the job count, and job cards.
- [ ] Each card: logo or first letter, title, company, chips (work mode, type, region, pay), how long ago it was posted. The featured job has a yellow border and "Featured" label and comes first.
- [ ] Type in the search box: results update as you type (by title, company or summary). The address bar updates too.
- [ ] Change each filter (category, work mode, region, type, experience, date posted): results update instantly and the address bar keeps the filters.
- [ ] Copy the filtered address into a new tab: the same results appear.
- [ ] Filter by **Africa**: Africa jobs **and** Worldwide jobs appear.
- [ ] **Include closed jobs** shows the closed sample with a "Closed" label.
- [ ] **Clear filters** resets everything. A search with no results shows a friendly "No jobs match" box.
- [ ] `/jobs/remote` shows only remote jobs and no work-mode filter. `/jobs/seo` shows "SEO jobs", its intro, only SEO jobs and no category filter.
- [ ] At phone width the filters are behind a **Filters** button; on desktop they're a sidebar.
- [ ] With more than 20 jobs, Previous/Next and page numbers appear and keep your filters (add jobs or duplicate a few to see this).
- [ ] In **Site settings → Navigation**, the link dropdowns now include a "Job categories" group.

### D4. Job page
Open any sample job from `/jobs`:
- [ ] Breadcrumb (Jobs / category / title), logo or first letter, title, company (links to their site), chips (work mode, type, region, pay).
- [ ] One blue **Apply now** button that opens the employer's page in a new tab, with "Opens … website in a new tab" under it.
- [ ] "About the role" (paragraphs as typed) and "What they're looking for" (a tick list from the requirements).
- [ ] Facts box: category, where (region · location · country), work mode, type, experience, pay, posted, closes (if set), **Last checked {date}**, and the source link.
- [ ] "More {category} jobs" lists other open jobs in the same category; "Guides to help you land it" shows articles from the job category's related blog category (publish an article in **SEO & Content** to see it on SEO/Content Writing jobs).
- [ ] The closed sample job (`Copy Editor (closed sample)`, use "Include closed jobs") shows "This job is closed" with a link to open jobs, and **no** apply button.
- [ ] Logged in as owner: "Edit in dashboard" bar with "Last checked …". In the dashboard, **View on site** opens the job.
- [ ] Change a job's category in the dashboard: the old address redirects to the new one.
- [ ] The sample article (`/career/find-your-first-remote-job`) shows "Ready to put this into practice?" with links to the job categories linked to Career.
- [ ] Optional: paste a job address into Google's Rich Results Test (https://search.google.com/test/rich-results) once the site is live, to confirm the job data is valid.

---

## E. Homepage, search and forms

### E1. Search
- [ ] The header has a magnifying-glass button on every page; the phone menu has a search box at the top.
- [ ] `/search` with nothing typed suggests "All jobs" and the blog categories.
- [ ] Search for part of an article title (e.g. `remot`) and for a word that only appears inside an article's text: both find it. Drafts never appear.
- [ ] Search for a company name or a word from a job summary: matching **open** jobs appear above the articles, with "See all matching jobs" (opens the job list with that keyword).
- [ ] Closed jobs never appear in search.
- [ ] A search with no results shows "Nothing found for …" with suggestions.
- [ ] More than 9 matching articles: page links appear at the bottom.

### E2. Homepage
- [ ] The homepage shows, in order: the top banner (tagline, headline, text, main button + second link), job search with the latest jobs, featured guides, the blue "Publish your work" box, latest articles, the featured resource, and (from E4) the newsletter box.
- [ ] Type a keyword or pick a category in the homepage job search and press **Find jobs**: the job list opens with that filter.
- [ ] **Site settings → Homepage**: change a section title and save: the homepage updates.
- [ ] Switch a section off: it disappears. Switch it back on: it returns.
- [ ] Change "How many jobs to show" / "How many articles to show": the homepage shows that many.
- [ ] Mark an article as **Feature on the homepage**: it appears under Featured guides (and isn't repeated under Latest articles).
- [ ] Turn on **Featured resource**, give it a title, text, image and button: it appears near the bottom.
- [ ] Sections with nothing to show yet (no jobs, no featured articles) stay hidden instead of showing empty boxes.
- [ ] Check the homepage at phone width.

### E3. Contact form and Inbox
- [ ] `/contact` shows the page text and a **Send us a message** form below it.
- [ ] Send it empty: each field shows a friendly message in red, and what you typed is kept.
- [ ] Send a real message: a green "Thanks, … Your message has been sent" appears in place of the form.
- [ ] Run `php artisan queue:work --stop-when-empty`: an email "New message: …" goes to the address in **Site settings → General → Send contact-form messages to**; replying to it answers the visitor.
- [ ] Sending more than 5 messages within a minute is blocked ("Too many requests"); after a minute it works again.
- [ ] Dashboard → **People → Inbox**: the menu shows a count of unread messages; unread messages are bold with an envelope icon.
- [ ] Open a message: full text, name, email (click to copy), date; it is now marked read.
- [ ] **Reply by email** opens your email app with "Re: subject" filled in.
- [ ] **Mark as unread** takes you back to the Inbox with that message bold again.
- [ ] Tabs **All / Unread**, search, the Read/Unread filter, and bulk mark read / unread / delete all work.

### E4. Newsletter
Sign-up:
- [ ] The homepage (bottom) and every article (blue box at the end) have an email box with **Subscribe** and "We'll email you a link to confirm".
- [ ] Enter a bad email: "That email address doesn't look right." under the box, on the same form you used.
- [ ] Enter a real email: "Almost done! Check your inbox…" replaces the form.
- [ ] Run `php artisan queue:work --stop-when-empty`, then open the confirmation email in `storage/logs/laravel.log` and click **Yes, subscribe me**: a "You're subscribed!" page.
- [ ] Open the same confirmation link 8+ days later (or change one letter in it): a friendly "This link has expired" page with a fresh sign-up box.
- [ ] Signing up again with the same email never creates a duplicate.

Dashboard → **People → Subscribers**:
- [ ] Each subscriber shows email, name, status (Waiting for confirmation / Subscribed / Unsubscribed), where they signed up, "In Brevo", and when they joined. The Status filter works.
- [ ] **Download CSV** downloads a spreadsheet of all subscribers with their status.
- [ ] Delete a subscriber: it warns that they're also removed from Brevo (if connected).

Brevo (optional: needs a free Brevo account):
- [ ] **Site settings → Integrations → Newsletter**: with nothing filled in, **Test connection** says to add the API key and list ID first.
- [ ] Add a real Brevo API key and list ID, save, **Test connection**: "Connected to Brevo as …". A wrong key gives a clear error.
- [ ] Confirm a new subscription: after the queue runs, the contact appears in your Brevo list and "In Brevo" turns green.
- [ ] **Sync subscribers to Brevo** sends every confirmed subscriber (safe to repeat).
- [ ] Unsubscribe link (`/newsletter/unsubscribe/…`, from a signed link) removes them from the Brevo list too.

### E5. Owner dashboard
Dashboard home (`/admin`):
- [ ] **Quick add** box with **New job**, **New article** and **View site** buttons.
- [ ] Four boxes: **Articles to review**, **Jobs to re-check**, **Unread messages**, **New subscribers** (confirmed in the last 7 days). Each is yellow/blue when something needs you and green when all is done.
- [ ] Clicking each box opens the right list, already on the right tab (Awaiting review / Needs re-check / Unread).
- [ ] When a writer submits an article, an **Awaiting your review** list appears (oldest first) with a **Review** button; it disappears when nothing is waiting.

---

## F. SEO, AI search and speed

### F1. Search engine basics
Tip: in Chrome, right-click a page → **View page source** to see these tags; or use https://search.google.com/test/rich-results once the site is live.
- [ ] Each page's browser tab title ends with " | Solo Hustle" (from **Site settings → SEO defaults → Title ending**).
- [ ] Share an article link in WhatsApp/LinkedIn/X (live site): it shows the title, summary and cover image. Pages without an image use the **Default share image**.
- [ ] Until launch, page source contains `noindex, nofollow` on every page. Turn off **Ask search engines not to list the site** and it disappears (except on drafts, closed jobs and search results).
- [ ] Page source on an article contains Article, BreadcrumbList and (if it has FAQs) FAQPage data; on a job: JobPosting and BreadcrumbList; on an author page: Person; on every page: Organization and WebSite (with search).
- [ ] An article's "Updated" date matches its `dateModified` in the page source.
- [ ] `/sitemap.xml` lists the homepage, job pages, open jobs, pages, categories, published articles and author pages, but no drafts or closed jobs. It is rebuilt every night.
- [ ] **Site settings → Integrations → Search Console code**: after saving, page source contains `google-site-verification`.
- [ ] Live site only: with a **Google Analytics ID** saved, a slim cookie bar appears at the bottom; nothing is tracked until **Accept**. **Decline** hides it for good; the footer's **Cookie settings** link brings it back. Without an ID there is no bar.

### F2. AI assistants and search engines
Dashboard → **Site settings → AI & search engines**:
- [ ] Six switches (GPTBot, OAI-SearchBot, ClaudeBot, PerplexityBot, Google-Extended, CCBot), all on, with a plain explanation of who each one is.
- [ ] Before launch, `/robots.txt` says `Disallow: /` for everyone.
- [ ] Turn off **Ask search engines not to list the site** (SEO defaults): `/robots.txt` now allows the site, hides `/admin` and `/writer`, and links the sitemap.
- [ ] Switch off GPTBot and save: `/robots.txt` now has a `User-agent: GPTBot / Disallow: /` block. Switch it back on: the block disappears.
- [ ] `/llms.txt` lists the site name and description, key pages, job pages, blog topics and the latest 20 articles. Publish an article: it appears straight away. Switch the guide off: `/llms.txt` shows "not found".
- [ ] Paste a Bing verification meta tag and save: only the code is kept, and page source shows `msvalidate.01`.
- [ ] Switch **IndexNow** on and save: the helper text now shows "Your key file: …/xxxxxxxx.txt", and that address shows the key. (Pings are only sent from the live site.)
- [ ] Turn the site-wide "discourage" switch back on afterwards if the site isn't launched yet.

### F3. Speed
- [ ] Visit any public page twice while logged out: the second load is noticeably quick (developer: the response has an `X-Page-Cache: hit` header).
- [ ] Edit a page, article, job, category or a writer's name in the dashboard: the public page shows the change immediately (the cache clears itself).
- [ ] Change any Site setting: every page reflects it immediately.
- [ ] Logged in as owner, pages are never cached (you always see the "Edit in dashboard" bar).
- [ ] Contact and newsletter forms still work on cached pages, and their thank-you / error messages always appear.
- [ ] Images in article text load only as you scroll down to them.
- [ ] Lighthouse (Chrome → right-click → Inspect → **Lighthouse** tab → Mobile → Analyze) on the homepage, `/jobs`, a job page and an article: Performance, Accessibility, Best practices and SEO should each be 90+. (SEO shows lower until the "discourage search engines" switch is turned off at launch.)
