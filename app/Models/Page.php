<?php

declare(strict_types=1);

namespace App\Models;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/** A standalone page such as About or Privacy Policy, served at /{slug}. */
#[Fillable(['title', 'slug', 'body', 'seo_title', 'meta_description', 'is_published'])]
class Page extends Model
{
    protected static function booted(): void
    {
        static::updated(function (Page $page) {
            // Only live pages can have been linked to, so only they need a redirect.
            if ($page->wasChanged('slug') && $page->getOriginal('is_published')) {
                Redirect::recordMove('/'.$page->getOriginal('slug'), '/'.$page->slug);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function url(): string
    {
        return url('/'.$this->slug);
    }

    /**
     * The body as safe HTML for the public site.
     */
    public function renderedBody(): HtmlString
    {
        // The renderer fails on empty content, and a new page may have no text yet.
        if (blank($this->body)) {
            return new HtmlString('');
        }

        $html = RichContentRenderer::make($this->body)
            ->fileAttachmentsDisk(config('media.disk'))
            ->fileAttachmentsVisibility('public')
            ->toHtml();

        return new HtmlString($html);
    }
}
