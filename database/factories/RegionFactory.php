<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return ['name' => $name, 'slug' => Str::slug($name)];
    }

    public function worldwide(): static
    {
        return $this->state(fn (array $attributes) => ['name' => 'Worldwide', 'slug' => 'worldwide', 'is_worldwide' => true]);
    }
}
