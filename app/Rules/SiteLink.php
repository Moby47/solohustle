<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** A link the owner types into a menu or button: a page on this site (/about) or a full http(s) address. */
class SiteLink implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $isInternalPath = is_string($value) && preg_match('#^/(?!/)#', $value) === 1;
        $isWebAddress = is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $value) === 1;

        if (! $isInternalPath && ! $isWebAddress) {
            $fail('Use a page on this site starting with / (like /about), or a full web address starting with https://.');
        }
    }
}
