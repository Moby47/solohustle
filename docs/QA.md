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
