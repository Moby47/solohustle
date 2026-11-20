<?php

declare(strict_types=1);

/*
 * Builds docs/QA.pdf, the client copy of docs/QA.md.
 *
 * Usage: php docs/build-qa-pdf.php [site address]
 *   e.g. php docs/build-qa-pdf.php https://staging.solohustle.com
 * Without an address, local links are shown as "[site address]".
 * Needs Google Chrome (or set CHROME_PATH).
 */

use League\CommonMark\GithubFlavoredMarkdownConverter;

require __DIR__.'/../vendor/autoload.php';

$siteAddress = rtrim($argv[1] ?? '[site address]', '/');
$markdown = file_get_contents(__DIR__.'/QA.md');

// The client gets their own short introduction instead of the developer's set-up notes and logins.
$markdown = preg_replace('/^.*?\R---\R/s', '', $markdown, 1);
$markdown = str_replace('http://solohustle.test', $siteAddress, $markdown);

$body = (new GithubFlavoredMarkdownConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]))
    ->convert($markdown)
    ->getContent();

// Steps that need a terminal are for the developer; tag them so the client can skip them.
$body = preg_replace_callback('#<li>(.*?)</li>#s', function (array $match): string {
    // Terminal commands and reading the local email log are developer-only; the live site sends real emails.
    $isDeveloperStep = preg_match('#<code>(php|npm|composer) |storage/logs#', $match[1]) === 1;

    return $isDeveloperStep
        ? '<li class="dev"><span class="tag">Developer</span> '.$match[1].'</li>'
        : $match[0];
}, $body);

$date = date('j F Y');

$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Solo Hustle – Website test checklist</title>
<style>
    @page { size: A4; margin: 18mm 16mm 20mm; }
    :root { --blue: #0750A1; --yellow: #F5D522; --lavender: #B99BD8; --ink: #1F2933; --muted: #52606D; --line: #DDE3EA; --surface: #F2F5F9; }
    * { box-sizing: border-box; }
    body { font-family: "Segoe UI", Inter, Arial, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.5; margin: 0; }
    .cover { height: 250mm; display: flex; flex-direction: column; justify-content: space-between; page-break-after: always; }
    .brand { font-size: 22pt; font-weight: 800; color: var(--blue); letter-spacing: -0.02em; }
    .brand span { color: var(--yellow); }
    .cover h1 { font-size: 34pt; line-height: 1.1; margin: 0 0 8mm; letter-spacing: -0.02em; }
    .cover .lead { font-size: 13pt; color: var(--muted); max-width: 140mm; }
    .bar { height: 3mm; width: 40mm; background: var(--yellow); border-radius: 2mm; margin-bottom: 10mm; }
    .howto { background: var(--surface); border-left: 4px solid var(--blue); border-radius: 3mm; padding: 6mm 7mm; }
    .howto h2 { margin: 0 0 3mm; font-size: 13pt; border: 0; padding: 0; }
    .howto ul { margin: 0; padding-left: 5mm; }
    .howto li { margin: 1.5mm 0; list-style: disc; }
    .meta { color: var(--muted); font-size: 9.5pt; }
    hr { display: none; }
    h2 { font-size: 18pt; color: var(--blue); margin: 0 0 4mm; padding-bottom: 2mm; border-bottom: 2px solid var(--blue); page-break-before: always; }
    h3 { font-size: 12.5pt; margin: 7mm 0 2mm; page-break-after: avoid; }
    h3 + p, h3 + ul { page-break-before: avoid; }
    p { margin: 2mm 0; }
    ul { padding-left: 0; margin: 2mm 0; }
    li { list-style: none; margin: 1.6mm 0; padding-left: 7mm; position: relative; page-break-inside: avoid; }
    li input[type=checkbox] { -webkit-appearance: none; appearance: none; position: absolute; left: 0; top: 0.6mm; width: 3.8mm; height: 3.8mm; border: 1.5px solid var(--blue); border-radius: 0.8mm; margin: 0; }
    li.dev { color: var(--muted); }
    .tag { display: inline-block; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; background: var(--surface); border: 1px solid var(--line); color: var(--muted); border-radius: 1mm; padding: 0 1.5mm; margin-right: 1mm; }
    code { font-family: Consolas, monospace; font-size: 9pt; background: var(--surface); border-radius: 1mm; padding: 0.2mm 1mm; }
    strong { font-weight: 650; }
    a { color: var(--blue); text-decoration: none; }
</style>
</head>
<body>
<section class="cover">
    <div class="brand">Solo Hustle<span>.</span></div>
    <div>
        <div class="bar"></div>
        <h1>Website test checklist</h1>
        <p class="lead">A step-by-step list of everything to check on the new Solo Hustle website, section by section.</p>
    </div>
    <div class="howto">
        <h2>How to use this checklist</h2>
        <ul>
            <li>Work through one section at a time and tick each box when it works as described.</li>
            <li>If something doesn't look or work right, note the step (for example "C5") and what you saw, and send it to your developer.</li>
            <li>Check pages on a phone as well as a computer.</li>
            <li>Steps marked <span class="tag">Developer</span> are technical checks your developer does; you can skip them.</li>
            <li>Your developer will send you the website address and your login details.</li>
        </ul>
    </div>
    <p class="meta">Version of {$date}</p>
</section>
{$body}
</body>
</html>
HTML;

$htmlPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'solohustle-qa.html';
$pdfPath = __DIR__.DIRECTORY_SEPARATOR.'QA.pdf';
file_put_contents($htmlPath, $html);

$chrome = getenv('CHROME_PATH') ?: current(array_filter([
    'C:\Program Files\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    '/usr/bin/google-chrome',
], 'is_file'));

if (! $chrome) {
    fwrite(STDERR, "Chrome not found. Set CHROME_PATH.\n");
    exit(1);
}

$command = sprintf(
    '"%s" --headless=new --disable-gpu --no-pdf-header-footer --print-to-pdf=%s %s 2>&1',
    $chrome,
    escapeshellarg($pdfPath),
    escapeshellarg('file:///'.str_replace('\\', '/', $htmlPath)),
);

exec($command, $output, $exitCode);
@unlink($htmlPath);

if ($exitCode !== 0 || ! is_file($pdfPath)) {
    fwrite(STDERR, "PDF failed:\n".implode("\n", $output)."\n");
    exit(1);
}

echo 'Wrote '.$pdfPath.' ('.round(filesize($pdfPath) / 1024).' KB)'.PHP_EOL;
