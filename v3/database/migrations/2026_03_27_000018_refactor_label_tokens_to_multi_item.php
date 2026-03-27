<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refactor label tokens to support multiple item types per token.
 *
 * Moves item_type_id and quantity from ecc_storage_label_tokens into a
 * new ecc_storage_label_token_items pivot table.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
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

        // Migrate existing rows into the pivot table
        DB::statement('
            INSERT INTO ecc_storage_label_token_items (label_token_id, item_type_id, quantity)
            SELECT id, item_type_id, quantity
            FROM ecc_storage_label_tokens
            WHERE item_type_id IS NOT NULL
        ');

        Schema::table('ecc_storage_label_tokens', function (Blueprint $table) {
            $table->dropForeign(['item_type_id']);
            $table->dropColumn(['item_type_id', 'quantity']);
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('ecc_storage_label_tokens', function (Blueprint $table) {
            $table->unsignedInteger('item_type_id')->nullable()->after('token');
            $table->unsignedInteger('quantity')->default(1)->after('item_type_id');

            $table->foreign('item_type_id')
                ->references('id')
                ->on('ecc_storage_item_types');
        });

        // Restore first item per token back to the parent row
        DB::statement('
            UPDATE ecc_storage_label_tokens t
            INNER JOIN (
                SELECT label_token_id, item_type_id, quantity
                FROM ecc_storage_label_token_items
                WHERE id IN (
                    SELECT MIN(id) FROM ecc_storage_label_token_items GROUP BY label_token_id
                )
            ) i ON t.id = i.label_token_id
            SET t.item_type_id = i.item_type_id, t.quantity = i.quantity
        ');

        Schema::dropIfExists('ecc_storage_label_token_items');
    }
};
