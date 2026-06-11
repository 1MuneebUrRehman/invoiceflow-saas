<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events stay enabled: invoice public_id generation (HasUlids)
     * relies on the creating hook.
     */
    public function run(): void
    {
        $this->call(DemoTenantSeeder::class);
    }
}
