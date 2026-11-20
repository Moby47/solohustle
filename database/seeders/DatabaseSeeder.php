<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database: essentials everywhere, demo content only on a developer's machine.
     */
    public function run(): void
    {
        $this->call(EssentialsSeeder::class);

        if ($this->container->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
