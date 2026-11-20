<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** One tab of site settings, stored as JSON. Read through App\Support\Settings, not directly. */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'value' => 'array',
        ];
    }
}
