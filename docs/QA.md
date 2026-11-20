# Solo Hustle — QA checklist

What to check after each sub-step. Work top to bottom, or jump to any section.
Tick a box when it passes. If something fails, note the step number and what you saw.

## Before you start

| What | Value |
|---|---|
| Public site | http://solohustle.test |
| Owner dashboard | http://solohustle.test/admin — `admin@solohustle.test` / `password` |
| Writer area | http://solohustle.test/writer — `writer@solohustle.test` / `password` |
| Emails (local) | Not sent. They are written to the end of `storage/logs/laravel.log` |
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
