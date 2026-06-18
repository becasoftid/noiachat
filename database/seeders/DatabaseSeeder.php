<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            TenancySeeder::class,
            BillingSeeder::class,
            ChannelSeeder::class,
            AdminUserSeeder::class,
            TemplateSeeder::class,
            ContactSeeder::class,
            ConsentSeeder::class,
        ]);
    }
}
