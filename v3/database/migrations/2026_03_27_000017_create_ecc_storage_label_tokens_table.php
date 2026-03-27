<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the ecc_storage_label_tokens table for single-use physical label tokens.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('ecc_storage_label_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->char('token', 36)->unique();
            $table->unsignedInteger('item_type_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('note', 255)->nullable();
            $table->string('source', 50)->default('mint');
            $table->unsignedInteger('source_char_id')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('created_at');
            $table->unsignedInteger('claimed_by')->nullable();
            $table->unsignedInteger('claimed_at')->nullable();
            $table->unsignedInteger('expires_at')->nullable();

            $table->foreign('item_type_id')
                ->references('id')
                ->on('ecc_storage_item_types');

            $table->index('claimed_by', 'idx_claimed_by');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecc_storage_label_tokens');
    }
};
