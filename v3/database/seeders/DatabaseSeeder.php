<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Main database seeder that calls all storage seeders.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StorageCategorySeeder::class,
            StorageItemTypeSeeder::class,
            StorageSettingsSeeder::class,
        ]);
    }
}
