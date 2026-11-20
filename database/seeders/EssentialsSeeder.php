<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** What the live site needs to start: the admin account and the essential pages. Safe to run more than once. */
class EssentialsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedPages();
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
