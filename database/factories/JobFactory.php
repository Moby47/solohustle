<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkMode;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Content Writer', 'SEO Specialist', 'Support Agent', 'Product Designer', 'Virtual Assistant']),
            'company_name' => fake()->company(),
            'company_url' => fake()->url(),
            'job_category_id' => JobCategory::factory(),
            'region_id' => Region::factory(),
            'work_mode' => WorkMode::Remote,
            'employment_type' => EmploymentType::FullTime,
            'experience_level' => ExperienceLevel::Mid,
            'summary' => fake()->paragraph(),
            'requirements' => implode("\n", fake()->sentences(3)),
            'apply_url' => 'https://example.com/careers/'.fake()->slug(),
            'status' => JobStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::Closed]);
    }
}
