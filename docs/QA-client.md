# Solo Hustle — Quick test guide

## Before you start

**Test accounts**

| Who | Where to log in | Email | Password |
|---|---|---|---|
| You (the owner) | [site address]/admin | `admin@solohustle.test` | `password` |
| A guest writer | [site address]/writer | `writer@solohustle.test` | `password` |
| A visitor | Just open the website | No login needed | |

These are test accounts only. They will be replaced with your real login before the site goes live.

**Good to know**
- Try everything on your **phone** as well as a computer.
- When testing as the writer, use a **private / incognito window**, so you stay logged in as the owner in your normal window.
- The site has sample jobs and articles in it (with colourful placeholder pictures) so you can see how everything looks.
- Some emails (sign-up confirmations, contact messages) may not arrive while we're testing. Your developer will tell you if they are switched on.
- Until launch, the site is hidden from Google on purpose.

---

## 1. As a visitor

**Homepage**
- [ ] The homepage looks clean and on-brand, with a main button, latest jobs, featured guides, latest articles and a newsletter box.
- [ ] The menu at the top works. On a phone, the ☰ button opens a slide-out menu.

**Jobs**
- [ ] Open **Jobs**. Type a word in the search box: the list updates as you type.
- [ ] Try the filters (category, work mode, region, job type, experience, date posted). **Clear filters** resets them.
- [ ] Open a job: you see the summary, requirements, pay, "Last checked" date and a blue **Apply now** button that opens the employer's page in a new tab.
- [ ] Open **Remote jobs** and one job category (for example **SEO**) from the buttons at the top of the jobs page.

**Articles**
- [ ] Open an article: title, author, date, a "Quick answer" box, table of contents ("In this article"), the article text, FAQs, sources and "More in …" at the bottom.
- [ ] Click the author's name: their profile page with their articles.
- [ ] Open a blog category from the menu (for example **Career**).

**Search, contact and newsletter**
- [ ] Click the magnifying glass and search for a word: matching jobs and articles appear.
- [ ] Go to **Contact**, send a message: you see a thank-you message.
- [ ] Enter your email in a newsletter box: you're told to check your inbox to confirm.
- [ ] Open **Publish with us** (top-right button): it explains how writing for the site works.
- [ ] Type a made-up address, like `[site address]/nothing-here`: a friendly "We couldn't find that page" screen.

---

## 2. As a guest writer

*(In a private window.)*
- [ ] Go to **[site address]/writer/register** and sign up with your own email. You're asked to confirm your email address.
- [ ] Log in as `writer@solohustle.test` instead. You see your dashboard with "In progress", "With the editor" and "Published".
- [ ] Open your **Profile** (top-right menu): add a photo, headline and short bio, then save.
- [ ] **My articles → New article**: write a title, choose a category, write some text (at least 300 words) and a summary, then save.
- [ ] Click **Preview** to see how it will look on the site.
- [ ] Click **Submit for review**, tick both boxes and confirm. The article now says "Awaiting review" and can no longer be edited.

---

## 3. As the owner

**Your dashboard**
- [ ] Log in at **[site address]/admin**. You see boxes for articles to review, jobs to re-check, unread messages and new subscribers, plus **New job** and **New article** buttons.

**Reviewing a writer's article**
- [ ] Open the article the writer just submitted (from "Awaiting your review" or **Content → Articles**).
- [ ] Try **Other decisions → Ask for changes** with a short note. Back in the writer's window, the article can be edited again and shows your note.
- [ ] Have the writer submit it again, then click **Publish as is**. It's now live on the website.

**Jobs**
- [ ] **New job**: add a job you found online (title, company, category, region, summary in your own words, apply link). Save, then click **View on site**.
- [ ] In the jobs list, try **Mark verified**, **Close** and **Reopen** from a job's ⋮ menu, and **Duplicate** to copy it.
- [ ] Look at the **Needs re-check** tab: jobs you haven't checked for 30 days appear there.

**Articles and pages**
- [ ] **New article**: write one, add a cover picture, set **Status** to Published and save. It appears on the website.
- [ ] **Content → Pages**: edit **About** and save. Click **View** to see the change.

**Running the site**
- [ ] **Settings → Site settings**: change the tagline, upload your logo, change a homepage title, and reorder the top menu. Save and check the website.
- [ ] **People → Inbox**: read the contact message you sent as a visitor. **Reply by email** opens your email app.
- [ ] **People → Subscribers**: see newsletter sign-ups; **Download CSV** gives you a spreadsheet.
- [ ] **People → Writers**: open the test writer. Try **Suspend** (they can no longer log in), then **Unsuspend**.

---

## 4. Found a problem?

For each problem, please send your developer:
1. **Where**: the page address, or the menu you were in.
2. **What you did**: the steps, in order.
3. **What happened**, and what you expected instead.
4. **A screenshot** if you can, and whether it was on a phone or computer.

The full, detailed checklist (QA.pdf) covers every feature if you want to go deeper.
