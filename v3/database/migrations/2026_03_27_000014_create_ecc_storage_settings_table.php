<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the ecc_storage_settings table for runtime configuration.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('ecc_storage_settings', function (Blueprint $table) {
            $table->string('key_name', 50)->primary();
            $table->string('value', 255);
            $table->unsignedInteger('updated_at');
            $table->unsignedInteger('updated_by');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecc_storage_settings');
    }
};
