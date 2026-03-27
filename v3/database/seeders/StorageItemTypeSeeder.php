<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed the Sonuren item type into ecc_storage_item_types.
 */
class StorageItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ecc_storage_item_types')->insertOrIgnore([
            'id' => 1,
            'name' => 'Sonuren',
            'description' => 'In-game currency',
            'category_id' => 1,
            'stackable' => 1,
            'is_system' => 1,
            'created_at' => time(),
            'created_by' => 0,
        ]);
    }
}
