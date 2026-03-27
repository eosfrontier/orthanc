<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the ecc_storage_categories table for item type grouping.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('ecc_storage_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->unsignedInteger('created_at');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('deleted_at')->nullable();
            $table->tinyInteger('is_system')->default(0);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecc_storage_categories');
    }
};
