<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace simple UNIQUE(name) indexes on categories and item_types with
 * soft-delete-aware uniqueness using a stored generated column.
 *
 * The generated column `active_name` equals `name` when the row is active
 * (deleted_at IS NULL) and NULL when soft-deleted. Since MySQL allows
 * multiple NULLs in a unique index, this lets you re-create a record
 * with the same name as a previously soft-deleted one.
 */
return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // --- categories ---
        DB::statement('ALTER TABLE `ecc_storage_categories` DROP INDEX `ecc_storage_categories_name_unique`');
        DB::statement('ALTER TABLE `ecc_storage_categories` ADD COLUMN `active_name` VARCHAR(100) GENERATED ALWAYS AS (IF(`deleted_at` IS NULL, `name`, NULL)) STORED AFTER `name`');
        DB::statement('ALTER TABLE `ecc_storage_categories` ADD UNIQUE INDEX `ecc_storage_categories_active_name_unique` (`active_name`)');

        // --- item_types ---
        DB::statement('ALTER TABLE `ecc_storage_item_types` DROP INDEX `ecc_storage_item_types_name_unique`');
        DB::statement('ALTER TABLE `ecc_storage_item_types` ADD COLUMN `active_name` VARCHAR(100) GENERATED ALWAYS AS (IF(`deleted_at` IS NULL, `name`, NULL)) STORED AFTER `name`');
        DB::statement('ALTER TABLE `ecc_storage_item_types` ADD UNIQUE INDEX `ecc_storage_item_types_active_name_unique` (`active_name`)');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // --- categories ---
        DB::statement('ALTER TABLE `ecc_storage_categories` DROP INDEX `ecc_storage_categories_active_name_unique`');
        DB::statement('ALTER TABLE `ecc_storage_categories` DROP COLUMN `active_name`');
        DB::statement('ALTER TABLE `ecc_storage_categories` ADD UNIQUE INDEX `ecc_storage_categories_name_unique` (`name`)');

        // --- item_types ---
        DB::statement('ALTER TABLE `ecc_storage_item_types` DROP INDEX `ecc_storage_item_types_active_name_unique`');
        DB::statement('ALTER TABLE `ecc_storage_item_types` DROP COLUMN `active_name`');
        DB::statement('ALTER TABLE `ecc_storage_item_types` ADD UNIQUE INDEX `ecc_storage_item_types_name_unique` (`name`)');
    }
};
