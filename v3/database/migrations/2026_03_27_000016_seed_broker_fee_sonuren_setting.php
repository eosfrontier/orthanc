<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the broker_fee_sonuren setting for existing databases.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        DB::table('ecc_storage_settings')->insertOrIgnore([
            'key_name'   => 'broker_fee_sonuren',
            'value'      => '20',
            'updated_at' => time(),
            'updated_by' => 0,
        ]);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('ecc_storage_settings')
            ->where('key_name', 'broker_fee_sonuren')
            ->delete();
    }
};
