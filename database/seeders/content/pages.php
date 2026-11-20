<?php

declare(strict_types=1);

/*
 * Starter text for the essential pages. The owner edits these in the dashboard; the seeder never overwrites them.
 * Legal pages are templates and must be reviewed before launch.
 */

$reviewNote = '<p><em>Template text: please review and adapt before the site goes live.</em></p>';

return [

    'about' => [
        'title' => 'About Solo Hustle',
        'meta_description' => 'Solo Hustle helps remote workers, freelancers and writers find real work and build skills, with hand-picked jobs and practical guides.',
        'body' => <<<'HTML'
            <p>Solo Hustle is a career and creator hub for people building independent working lives: remote employees, freelancers and writers.</p>
            <h2>What you'll find here</h2>
            <ul>
                <li><strong>Remote and online jobs</strong>, picked by hand. Every listing links to the employer's own page, and we re-check listings regularly.</li>
                <li><strong>Practical guides</strong> on careers, freelancing, SEO and content, and honest reviews of the tools we use.</li>
                <li><strong>A place to publish.</strong> Guest writers can share original work, reviewed by an editor, and use it as a portfolio sample.</li>
            </ul>
            <h2>What we don't do</h2>
            <p>No get-rich-quick schemes, no scraped job boards and no paid placements dressed up as advice. If something is ever sponsored, it will say so clearly.</p>
            <h2>Work. Write. Build.</h2>
            <p>That's the idea: find good work, sharpen your skills by writing about what you know, and build something of your own.</p>
            HTML,
    ],

    'contact' => [
        'title' => 'Contact us',
        'meta_description' => 'Get in touch with Solo Hustle about jobs, articles, guest writing or partnerships.',
        'body' => <<<'HTML'
            <p>We'd love to hear from you, whether you've spotted a problem with a job listing, have an idea for a guide, or want to work together.</p>
            <p>Email us at <a href="mailto:solohustlehub@gmail.com">solohustlehub@gmail.com</a> and we'll reply as soon as we can, usually within two working days.</p>
            <h2>Before you write</h2>
            <ul>
                <li><strong>About a job listing?</strong> Please include the job title or link.</li>
                <li><strong>Want to write for us?</strong> Read the <a href="/creator-guidelines">Creator Guidelines</a>, then <a href="/publish">sign up as a writer</a>.</li>
                <li><strong>Advertising?</strong> See <a href="/advertise">Advertise</a>.</li>
            </ul>
            HTML,
    ],

    'editorial-policy' => [
        'title' => 'Editorial Policy',
        'meta_description' => 'How Solo Hustle chooses jobs, reviews articles and keeps its content accurate and independent.',
        'body' => <<<'HTML'
            <p>We want every page on Solo Hustle to be useful, accurate and honest. This is how we work.</p>
            <h2>Jobs</h2>
            <ul>
                <li>Every job is added by hand and links to the employer's original listing.</li>
                <li>We write our own short summaries. We never copy or scrape job descriptions.</li>
                <li>We re-check open jobs regularly and show the date each one was last checked. Closed jobs are clearly marked.</li>
            </ul>
            <h2>Articles</h2>
            <ul>
                <li>Articles are written from real experience and checked by an editor before they are published.</li>
                <li>We cite our sources and update articles when things change. The "Updated" date shows the last real change.</li>
                <li>Guest writers confirm their work is original and that they have the right to publish it.</li>
            </ul>
            <h2>Independence</h2>
            <p>We don't accept payment to review a product or to list a job. If we ever publish sponsored content, it will be clearly labelled "Sponsored".</p>
            <h2>Corrections</h2>
            <p>If you spot a mistake, please <a href="/contact">tell us</a>. We fix errors quickly and note significant corrections.</p>
            HTML,
    ],

    'creator-guidelines' => [
        'title' => 'Creator Guidelines',
        'meta_description' => 'What to write, how reviews work and how to write guides that readers, search engines and AI assistants can understand.',
        'body' => <<<'HTML'
            <p>Thanks for wanting to write for Solo Hustle. Published pieces carry your name and link to your author page, so they make good portfolio samples.</p>
            <h2>What we publish</h2>
            <ul>
                <li>Practical guides on careers, freelancing, SEO and content, written from your own experience.</li>
                <li>Honest reviews of tools and services you have actually used.</li>
            </ul>
            <h2>The rules</h2>
            <ul>
                <li>Your work must be original and not published anywhere else.</li>
                <li>You must have the right to publish it (for example, no client work under an NDA).</li>
                <li>No affiliate links, hidden promotion or AI-generated filler.</li>
            </ul>
            <h2>How review works</h2>
            <ol>
                <li>Write your piece in the writer area and submit it for review.</li>
                <li>An editor reads it and either publishes it, asks for changes, or declines it, always with a note.</li>
                <li>Once published, contact us if you need something changed.</li>
            </ol>
            <h2>Writing for search and AI</h2>
            <p>Readers increasingly find answers through Google and AI assistants. These habits help them understand and quote your work:</p>
            <ul>
                <li><strong>Phrase headings as questions</strong> where it feels natural, like "How much should a beginner charge?"</li>
                <li><strong>Answer straight away</strong> in the first sentence under each heading, then explain.</li>
                <li><strong>Keep paragraphs short</strong>, and use lists and tables for steps and comparisons.</li>
                <li><strong>Give real examples</strong> and numbers from your own experience.</li>
                <li><strong>Cite your sources</strong> with links, so readers can check them.</li>
            </ul>
            HTML,
    ],

    'privacy-policy' => [
        'title' => 'Privacy Policy',
        'meta_description' => 'What personal data Solo Hustle collects, why, and how to contact us about it.',
        'body' => $reviewNote.<<<'HTML'
            <h2>What we collect</h2>
            <ul>
                <li><strong>Newsletter:</strong> your email address (and name, if you give it) when you subscribe.</li>
                <li><strong>Contact form:</strong> the name, email and message you send us.</li>
                <li><strong>Writer accounts:</strong> your name, email, profile details and the articles you submit.</li>
                <li><strong>Analytics:</strong> anonymous information about how the site is used, if analytics is enabled.</li>
            </ul>
            <h2>How we use it</h2>
            <p>Only to run the site: to send the newsletter you asked for, reply to your messages and manage writer accounts. We never sell your data.</p>
            <h2>Services we use</h2>
            <p>We may use Brevo to send emails and newsletters, and Google Analytics to understand site usage. They process data on our behalf.</p>
            <h2>Your choices</h2>
            <p>You can unsubscribe from emails at any time, and ask us to see, correct or delete your data by <a href="/contact">contacting us</a>.</p>
            HTML,
    ],

    'terms' => [
        'title' => 'Terms of Use',
        'meta_description' => 'The terms for using Solo Hustle, including job listings and guest writing.',
        'body' => $reviewNote.<<<'HTML'
            <h2>Using the site</h2>
            <p>Solo Hustle is provided for general information. Please use it lawfully and respectfully.</p>
            <h2>Job listings</h2>
            <p>We check listings carefully, but we are not the employer. Always apply through the employer's own page and use your own judgement. Never pay to apply for a job.</p>
            <h2>Guest writing</h2>
            <p>When you submit work you confirm it is original and that you have the right to publish it. You keep the copyright, and you give us permission to publish and display it on Solo Hustle.</p>
            <h2>Changes</h2>
            <p>We may update these terms. The latest version is always on this page.</p>
            HTML,
    ],

    'advertise' => [
        'title' => 'Advertise with us',
        'meta_description' => 'Reach remote workers, freelancers and writers on Solo Hustle.',
        'body' => <<<'HTML'
            <p>Solo Hustle reaches people building independent careers: remote workers, freelancers and writers.</p>
            <p>We don't run ads yet, but we're open to carefully chosen partnerships that genuinely help our readers. Anything paid is always clearly labelled "Sponsored".</p>
            <p>Interested? <a href="/contact">Get in touch</a> and tell us a little about what you have in mind.</p>
            HTML,
    ],

];
