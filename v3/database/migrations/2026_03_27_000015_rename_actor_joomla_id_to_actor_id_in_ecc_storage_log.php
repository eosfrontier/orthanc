<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename actor_joomla_id to actor_id in the ecc_storage_log table.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        if (Schema::hasColumn('ecc_storage_log', 'actor_joomla_id')) {
            Schema::table('ecc_storage_log', function (Blueprint $table) {
                $table->renameColumn('actor_joomla_id', 'actor_id');
            });
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ecc_storage_log', 'actor_id')) {
            Schema::table('ecc_storage_log', function (Blueprint $table) {
                $table->renameColumn('actor_id', 'actor_joomla_id');
            });
        }
    }
};
