-- Rollback all ecc_storage_* tables (reverse FK order)
-- Run this manually against the database if Laravel rollback is not available

DROP TABLE IF EXISTS ecc_storage_settings;
DROP TABLE IF EXISTS ecc_storage_log;
DROP TABLE IF EXISTS ecc_storage_inventory;
DROP TABLE IF EXISTS ecc_storage_item_types;
DROP TABLE IF EXISTS ecc_storage_categories;

-- Clean up migration rows
DELETE FROM migrations WHERE migration LIKE '%create_ecc_storage_%';
