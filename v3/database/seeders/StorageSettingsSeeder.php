<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seed default settings into ecc_storage_settings.
 */
class StorageSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('ecc_storage_settings')->insertOrIgnore([
            'key_name' => 'transfers_enabled',
            'value' => '1',
            'updated_at' => time(),
            'updated_by' => 0,
        ]);

        DB::table('ecc_storage_settings')->insertOrIgnore([
            'key_name' => 'broker_fee_sonuren',
            'value' => '20',
            'updated_at' => time(),
            'updated_by' => 0,
        ]);
    }
}
