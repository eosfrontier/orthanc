<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the ecc_storage_log table for append-only audit trail.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('ecc_storage_log', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('item_type_id');
            $table->integer('quantity');
            $table->unsignedInteger('source_char_id')->nullable();
            $table->unsignedInteger('target_char_id')->nullable();
            $table->unsignedInteger('actor_id');
            $table->string('action', 30);
            $table->tinyInteger('brokered')->default(0);
            $table->string('note', 255)->nullable();
            $table->unsignedInteger('created_at');

            $table->index(['source_char_id', 'created_at'], 'idx_source_char');
            $table->index(['target_char_id', 'created_at'], 'idx_target_char');
            $table->index(['item_type_id', 'created_at'], 'idx_item_type');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecc_storage_log');
    }
};
