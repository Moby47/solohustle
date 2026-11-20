<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Category;
use App\Models\Page;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A top-level web address part (e.g. "privacy-policy"): lowercase words joined by dashes, not used by the app,
 * and not taken by the other kind of top-level content (pages and categories share /{slug}).
 */
class PublicSlug implements ValidationRule
{
    /** @var array<class-string, string> */
    private const SHARED_ADDRESS_SPACE = [
        Page::class => 'page',
        Category::class => 'category',
    ];

    /**
     * @param  class-string  $for  The model being saved; its own table is checked by a normal unique rule.
     */
    public function __construct(private readonly string $for) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1) {
            $fail('Use only lowercase letters, numbers and dashes, like "privacy-policy".');

            return;
        }

        if (in_array($value, config('site.reserved_slugs'), true)) {
            $fail("\"{$value}\" is already used by the site itself. Please choose another address.");

            return;
        }

        foreach (self::SHARED_ADDRESS_SPACE as $model => $label) {
            if ($model !== $this->for && $model::where('slug', $value)->exists()) {
                $fail("A {$label} already uses this address. Please choose another.");

                return;
            }
        }
    }
}
