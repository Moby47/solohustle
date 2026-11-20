<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Where applicants can be based. A "worldwide" region is open to applicants anywhere. */
#[Fillable(['name', 'slug', 'is_worldwide', 'sort'])]
class Region extends Model
{
    /** @use HasFactory<RegionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_worldwide' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
