<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the ecc_storage_label_tokens and ecc_storage_label_token_items tables
 * for single-use physical label tokens with multi-item support.
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
            $table->string('note', 255)->nullable();
            $table->string('source', 50)->default('mint');
            $table->unsignedInteger('source_char_id')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('created_at');
            $table->unsignedInteger('claimed_by')->nullable();
            $table->unsignedInteger('claimed_at')->nullable();
            $table->unsignedInteger('expires_at')->nullable();

            $table->index('claimed_by', 'idx_claimed_by');
        });

        Schema::create('ecc_storage_label_token_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('label_token_id');
            $table->unsignedInteger('item_type_id');
            $table->unsignedInteger('quantity')->default(1);

            $table->foreign('label_token_id')
                ->references('id')
                ->on('ecc_storage_label_tokens')
                ->onDelete('cascade');

            $table->foreign('item_type_id')
                ->references('id')
                ->on('ecc_storage_item_types');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecc_storage_label_token_items');
        Schema::dropIfExists('ecc_storage_label_tokens');
    }
};
