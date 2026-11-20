<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** What the live site needs to start: admin account, essential pages and categories. Safe to run more than once. */
class EssentialsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedPages();
        $this->seedCategories();
    }

    private function seedCategories(): void
    {
        $categories = [
            'career' => ['Career', 'Find remote work, grow your skills and move your career forward.'],
            'freelancing' => ['Freelancing', 'Win clients, set your rates and run a sustainable freelance business.'],
            'seo-content' => ['SEO & Content', 'Write content that ranks, gets read and gets quoted.'],
            'reviews-guides' => ['Reviews & Guides', 'Honest reviews and step-by-step guides to the tools we use.'],
        ];

        $sort = 1;

        foreach ($categories as $slug => [$name, $description]) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'description' => $description, 'sort' => $sort++]);
        }
    }

    private function seedAdmin(): void
    {
        $email = config('site.admin_email');

        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = config('site.admin_password') ?: Str::password(16, symbols: false);

        $admin = new User(['name' => 'Admin', 'email' => $email, 'password' => $password]);
        $admin->forceFill(['role' => UserRole::Admin, 'email_verified_at' => now()])->save();

        if (blank(config('site.admin_password'))) {
            $this->command?->warn("Admin login created: {$email} / {$password} (change the password after signing in)");
        }
    }

    private function seedPages(): void
    {
        $pages = require __DIR__.'/content/pages.php';

        foreach ($pages as $slug => $page) {
            Page::firstOrCreate(['slug' => $slug], [...$page, 'is_published' => true]);
        }
    }
}
