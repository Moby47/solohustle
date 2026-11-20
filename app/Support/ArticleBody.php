<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Prepares safe article HTML for display: gives H2/H3 headings anchor ids for the table of contents,
 * and splits the text where the mid-article box goes (after the second section, i.e. before the third H2).
 */
final class ArticleBody
{
    /** The table of contents is only worth showing for longer articles. */
    public const MIN_HEADINGS_FOR_TOC = 3;

    /**
     * @param  list<array{level: int, text: string, id: string}>  $headings
     */
    private function __construct(
        public readonly HtmlString $beforeBreak,
        public readonly HtmlString $afterBreak,
        public readonly array $headings,
    ) {}

    public static function from(string $safeHtml): self
    {
        if (trim($safeHtml) === '') {
            return new self(new HtmlString(''), new HtmlString(''), []);
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$safeHtml.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->documentElement;
        $headings = [];
        $usedIds = [];
        $h2Count = 0;
        $breakBefore = null;

        // Images inside the text load only as the reader scrolls to them.
        foreach ($root->getElementsByTagName('img') as $image) {
            $image->setAttribute('loading', 'lazy');
            $image->setAttribute('decoding', 'async');
        }

        foreach ($root->childNodes as $node) {
            if (! $node instanceof DOMElement || ! in_array($node->tagName, ['h2', 'h3'], true)) {
                continue;
            }

            $text = trim($node->textContent);
            $id = self::uniqueId($text, $usedIds);
            $node->setAttribute('id', $id);
            $headings[] = ['level' => (int) substr($node->tagName, 1), 'text' => $text, 'id' => $id];

            if ($node->tagName === 'h2' && ++$h2Count === 3) {
                $breakBefore = $node;
            }
        }

        [$before, $after] = self::split($document, $root, $breakBefore);

        return new self(new HtmlString($before), new HtmlString($after), $headings);
    }

    public function hasTableOfContents(): bool
    {
        return count($this->headings) >= self::MIN_HEADINGS_FOR_TOC;
    }

    /**
     * @return array{string, string}
     */
    private static function split(DOMDocument $document, DOMNode $root, ?DOMNode $breakBefore): array
    {
        $before = '';
        $after = '';
        $reachedBreak = false;

        foreach ($root->childNodes as $node) {
            $reachedBreak = $reachedBreak || $node === $breakBefore;
            $html = $document->saveHTML($node);

            $reachedBreak ? $after .= $html : $before .= $html;
        }

        return [$before, $after];
    }

    /**
     * @param  array<string, true>  $usedIds
     */
    private static function uniqueId(string $text, array &$usedIds): string
    {
        $base = Str::slug($text) ?: 'section';
        $id = $base;
        $suffix = 2;

        while (isset($usedIds[$id])) {
            $id = $base.'-'.$suffix++;
        }

        $usedIds[$id] = true;

        return $id;
    }
}
