<?php

declare(strict_types=1);

/*
 * Builds the client PDFs from the Markdown test guides:
 *   docs/QA.md         -> docs/QA.pdf         (full, detailed checklist)
 *   docs/QA-client.md  -> docs/QA-client.pdf  (short quick test guide with the test accounts)
 *
 * Usage: php docs/build-qa-pdf.php [site address]
 *   e.g. php docs/build-qa-pdf.php https://staging.solohustle.com
 * Without an address, links are shown as "[site address]".
 * Needs Google Chrome (or set CHROME_PATH).
 */

use League\CommonMark\GithubFlavoredMarkdownConverter;

require __DIR__.'/../vendor/autoload.php';

$siteAddress = rtrim($argv[1] ?? '[site address]', '/');

$documents = [
    [
        'source' => 'QA.md',
        'pdf' => 'QA.pdf',
        'title' => 'Website test checklist',
        'lead' => 'A step-by-step list of everything to check on the new Solo Hustle website, section by section.',
        // The developer's set-up notes and local logins aren't for the client: start at the first section.
        'strip' => '/^.*?\R---\R/s',
        'howto' => [
            'Work through one section at a time and tick each box when it works as described.',
            'If something doesn\'t look or work right, note the step (for example "C5") and what you saw, and send it to your developer.',
            'Check pages on a phone as well as a computer.',
            'Steps marked <span class="tag">Developer</span> are technical checks your developer does; you can skip them.',
            'Your developer will send you the website address and your login details.',
        ],
    ],
    [
        'source' => 'QA-client.md',
        'pdf' => 'QA-client.pdf',
        'title' => 'Quick test guide',
        'lead' => 'The key things to try on the new Solo Hustle website: as a visitor, as a guest writer and as the owner.',
        'strip' => '/^# .*\R/',
        'howto' => [
            'Start with the test accounts on the next page.',
            'Go through the four short sections and tick each box when it works.',
            'Try it on your phone as well as a computer.',
            'Anything odd? Section 4 explains what to send your developer.',
        ],
    ],
];

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

foreach ($documents as $document) {
    $pdfPath = __DIR__.DIRECTORY_SEPARATOR.$document['pdf'];
    $html = renderHtml($document, $siteAddress);
    $htmlPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'solohustle-'.pathinfo($document['pdf'], PATHINFO_FILENAME).'.html';
    file_put_contents($htmlPath, $html);

    exec(sprintf(
        '"%s" --headless=new --disable-gpu --no-pdf-header-footer --print-to-pdf=%s %s 2>&1',
        $chrome,
        escapeshellarg($pdfPath),
        escapeshellarg('file:///'.str_replace('\\', '/', $htmlPath)),
    ), $output, $exitCode);
    @unlink($htmlPath);

    if ($exitCode !== 0 || ! is_file($pdfPath)) {
        fwrite(STDERR, "PDF failed for {$document['source']}:\n".implode("\n", $output)."\n");
        exit(1);
    }

    echo 'Wrote '.$pdfPath.' ('.round(filesize($pdfPath) / 1024).' KB)'.PHP_EOL;
}

/**
 * @param  array{source: string, title: string, lead: string, strip: string, howto: list<string>}  $document
 */
function renderHtml(array $document, string $siteAddress): string
{
    $markdown = file_get_contents(__DIR__.'/'.$document['source']);
    $markdown = preg_replace($document['strip'], '', $markdown, 1);
    $markdown = str_replace(['http://solohustle.test', '[site address]'], $siteAddress, $markdown);

    $body = (new GithubFlavoredMarkdownConverter(['html_input' => 'escape', 'allow_unsafe_links' => false]))
        ->convert($markdown)
        ->getContent();

    // Terminal commands and reading the local email log are developer-only; tag them so the client can skip them.
    $body = preg_replace_callback('#<li>(.*?)</li>#s', function (array $match): string {
        $isDeveloperStep = preg_match('#<code>(php|npm|composer) |storage/logs#', $match[1]) === 1;

        return $isDeveloperStep
            ? '<li class="dev"><span class="tag">Developer</span> '.$match[1].'</li>'
            : $match[0];
    }, $body);

    $title = $document['title'];
    $lead = $document['lead'];
    $howto = implode('', array_map(fn (string $item): string => "<li>{$item}</li>", $document['howto']));
    $date = date('j F Y');

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Solo Hustle – {$title}</title>
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
    .howto h2 { margin: 0 0 3mm; font-size: 13pt; border: 0; padding: 0; page-break-before: avoid; color: var(--ink); }
    .howto ul { margin: 0; padding-left: 5mm; }
    .howto li { margin: 1.5mm 0; list-style: disc; padding-left: 0; }
    .meta { color: var(--muted); font-size: 9.5pt; }
    hr { display: none; }
    h2 { font-size: 18pt; color: var(--blue); margin: 0 0 4mm; padding-bottom: 2mm; border-bottom: 2px solid var(--blue); page-break-before: always; }
    h3 { font-size: 12.5pt; margin: 7mm 0 2mm; page-break-after: avoid; }
    h3 + p, h3 + ul { page-break-before: avoid; }
    p { margin: 2mm 0; }
    ul, ol { padding-left: 0; margin: 2mm 0; }
    li { list-style: none; margin: 1.6mm 0; padding-left: 7mm; position: relative; page-break-inside: avoid; }
    ol li { list-style: decimal; margin-left: 5mm; padding-left: 1mm; }
    li input[type=checkbox] { -webkit-appearance: none; appearance: none; position: absolute; left: 0; top: 0.6mm; width: 3.8mm; height: 3.8mm; border: 1.5px solid var(--blue); border-radius: 0.8mm; margin: 0; }
    ul > li:not(:has(input))::before { content: ''; position: absolute; left: 1.4mm; top: 2.1mm; width: 1.6mm; height: 1.6mm; border-radius: 50%; background: var(--blue); }
    .howto li::before { display: none; }
    li.dev { color: var(--muted); }
    table { width: 100%; border-collapse: collapse; margin: 3mm 0 5mm; font-size: 10pt; }
    th, td { text-align: left; padding: 2.5mm 3mm; border-bottom: 1px solid var(--line); }
    th { background: var(--surface); font-weight: 650; }
    .tag { display: inline-block; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; background: var(--surface); border: 1px solid var(--line); color: var(--muted); border-radius: 1mm; padding: 0 1.5mm; margin-right: 1mm; }
    code { font-family: Consolas, monospace; font-size: 9pt; background: var(--surface); border-radius: 1mm; padding: 0.2mm 1mm; }
    strong { font-weight: 650; }
    em { color: var(--muted); }
    a { color: var(--blue); text-decoration: none; }
</style>
</head>
<body>
<section class="cover">
    <div class="brand">Solo Hustle<span>.</span></div>
    <div>
        <div class="bar"></div>
        <h1>{$title}</h1>
        <p class="lead">{$lead}</p>
    </div>
    <div class="howto">
        <h2>How to use this guide</h2>
        <ul>{$howto}</ul>
    </div>
    <p class="meta">Version of {$date}</p>
</section>
{$body}
</body>
</html>
HTML;
}
