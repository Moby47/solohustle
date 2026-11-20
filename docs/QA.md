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
