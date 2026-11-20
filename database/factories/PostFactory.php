<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => rtrim(fake()->sentence(6), '.'),
            'excerpt' => fake()->sentence(18),
            'body' => '<p>'.implode('</p><p>', fake()->paragraphs(4)).'</p>',
            'status' => PostStatus::Draft,
        ];
    }

    public function status(PostStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function published(): static
    {
        return $this->status(PostStatus::Published);
    }
}
