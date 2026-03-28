<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique composite index on (character_id, item_type_id) to prevent
 * duplicate inventory rows created by concurrent first-mint race conditions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecc_storage_inventory', function (Blueprint $table) {
            $table->unique(['character_id', 'item_type_id'], 'inventory_character_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecc_storage_inventory', function (Blueprint $table) {
            $table->dropUnique('inventory_character_item_unique');
        });
    }
};
