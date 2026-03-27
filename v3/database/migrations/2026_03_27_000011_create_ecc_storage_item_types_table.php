<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the ecc_storage_item_types table for defining storable items.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::create('ecc_storage_item_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->unsignedInteger('category_id');
            $table->tinyInteger('stackable')->default(1);
            $table->unsignedInteger('max_quantity')->nullable();
            $table->tinyInteger('is_system')->default(0);
            $table->unsignedInteger('created_at');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_at')->nullable();
            $table->unsignedInteger('deleted_at')->nullable();

            $table->foreign('category_id', 'fk_item_type_category')
                ->references('id')
                ->on('ecc_storage_categories');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecc_storage_item_types');
    }
};
