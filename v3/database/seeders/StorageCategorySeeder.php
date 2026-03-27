<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the Currency category into ecc_storage_categories.
 */
class StorageCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ecc_storage_categories')->insertOrIgnore([
            'id' => 1,
            'name' => 'Currency',
            'created_at' => time(),
            'created_by' => 0,
            'is_system' => 1,
        ]);
    }
}
