<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A top-level web address part (e.g. "privacy-policy"): lowercase words joined by dashes, not used by the app itself. */
class PublicSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) !== 1) {
            $fail('Use only lowercase letters, numbers and dashes, like "privacy-policy".');

            return;
        }

        if (in_array($value, config('site.reserved_slugs'), true)) {
            $fail("\"{$value}\" is already used by the site itself. Please choose another address.");
        }
    }
}
