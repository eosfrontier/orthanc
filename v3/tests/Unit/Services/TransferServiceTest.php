<?php

namespace Tests\Unit\Services;

use App\Exceptions\TransfersLockedException;
use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLog;
use App\Models\StorageSetting;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for TransferService covering happy-path transfers, gate checks,
 * cap enforcement, broker fee deduction, quantity validation, and transactional rollback.
 */
class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TransferService();
    }

    /**
     * Create a StorageItemType (and its required category) with sensible defaults.
     */
    private function createItemType(array $overrides = []): StorageItemType
    {
        $category = StorageCategory::create([
            'name'       => 'Test Category',
            'created_at' => time(),
            'created_by' => 1,
        ]);

        return StorageItemType::create(array_merge([
            'name'         => 'Test Item',
            'description'  => 'A test item',
            'category_id'  => $category->id,
            'stackable'    => true,
            'max_quantity'  => null,
            'is_system'    => false,
            'created_at'   => time(),
            'created_by'   => 1,
            'updated_at'   => time(),
        ], $overrides));
    }

    /**
     * Enable the transfers_enabled setting.
     */
    private function enableTransfers(): void
    {
        StorageSetting::create([
            'key_name'   => 'transfers_enabled',
            'value'      => '1',
            'updated_at' => time(),
            'updated_by' => 1,
        ]);
    }

    /**
     * Seed the broker_fee_sonuren setting (default 20).
     */
    private function seedBrokerFee(int $fee = 20): void
    {
        StorageSetting::updateOrCreate(
            ['key_name' => 'broker_fee_sonuren'],
            [
                'value'      => (string) $fee,
                'updated_at' => time(),
                'updated_by' => 1,
            ],
        );
    }

    /**
     * Create the Sonuren item type (id=1, is_system=1) with its Currency category.
     */
    private function createSonurenType(): StorageItemType
    {
        $category = StorageCategory::create([
            'name'       => 'Currency',
            'created_at' => time(),
            'created_by' => 0,
            'is_system'  => true,
        ]);

        return StorageItemType::create([
            'id'           => 1,
            'name'         => 'Sonuren',
            'description'  => 'In-game currency',
            'category_id'  => $category->id,
            'stackable'    => true,
            'max_quantity'  => null,
            'is_system'    => true,
            'created_at'   => time(),
            'created_by'   => 0,
            'updated_at'   => time(),
        ]);
    }

    /**
     * Seed a character's inventory with the given quantity.
     */
    private function seedInventory(int $characterId, int $itemTypeId, int $quantity): StorageInventory
    {
        return StorageInventory::create([
            'character_id' => $characterId,
            'item_type_id' => $itemTypeId,
            'quantity'     => $quantity,
            'updated_at'   => time(),
        ]);
    }

    public function test_transfer_moves_quantity_and_creates_log(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 10);
        $this->seedInventory(200, $itemType->id, 5);

        $result = $this->service->transfer(100, 200, $itemType->id, 3, 1, false, 'trade');

        $this->assertEquals(7, $result['source']->quantity);
        $this->assertEquals(8, $result['target']->quantity);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 7,
        ]);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 8,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $itemType->id,
            'quantity'        => 3,
            'source_char_id'  => 100,
            'target_char_id'  => 200,
            'action'          => 'transfer',
            'brokered'        => false,
            'note'            => 'trade',
        ]);
    }

    public function test_transfer_creates_target_inventory_if_not_exists(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 10);

        $result = $this->service->transfer(100, 200, $itemType->id, 4, 1);

        $this->assertEquals(6, $result['source']->quantity);
        $this->assertEquals(4, $result['target']->quantity);
        $this->assertEquals(200, $result['target']->character_id);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 4,
        ]);
    }

    public function test_brokered_transfer_deducts_fee_and_creates_log(): void
    {
        $this->enableTransfers();
        $this->seedBrokerFee();
        $sonuren = $this->createSonurenType();
        $itemType = $this->createItemType();

        // Source has 50 Sonuren and 5 items
        $this->seedInventory(100, $sonuren->id, 50);
        $this->seedInventory(100, $itemType->id, 5);
        $this->seedInventory(200, $itemType->id, 0);

        $result = $this->service->transfer(100, 200, $itemType->id, 2, 1, true, 'brokered deal');

        // Items transferred
        $this->assertEquals(3, $result['source']->quantity);
        $this->assertEquals(2, $result['target']->quantity);

        // Sonuren deducted
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $sonuren->id,
            'quantity'     => 30,
        ]);

        // Transfer log with brokered flag
        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $itemType->id,
            'action'          => 'transfer',
            'brokered'        => true,
            'note'            => 'brokered deal',
        ]);

        // Broker fee log
        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $sonuren->id,
            'quantity'        => 20,
            'source_char_id'  => 100,
            'action'          => 'broker_fee',
            'brokered'        => true,
        ]);
    }

    public function test_brokered_transfer_of_sonuren_deducts_fee_and_quantity(): void
    {
        $this->enableTransfers();
        $this->seedBrokerFee(20);
        $sonuren = $this->createSonurenType();

        // Source has 50 Sonuren, target has 10
        $this->seedInventory(100, $sonuren->id, 50);
        $this->seedInventory(200, $sonuren->id, 10);

        $result = $this->service->transfer(100, 200, $sonuren->id, 5, 1, true, 'sonuren trade');

        // 50 - 20 (fee) - 5 (transfer) = 25
        $this->assertEquals(25, $result['source']->quantity);
        $this->assertEquals(15, $result['target']->quantity);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $sonuren->id,
            'quantity'     => 25,
        ]);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $sonuren->id,
            'quantity'     => 15,
        ]);

        // Broker fee log
        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $sonuren->id,
            'quantity'        => 20,
            'source_char_id'  => 100,
            'action'          => 'broker_fee',
        ]);

        // Transfer log
        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $sonuren->id,
            'quantity'        => 5,
            'source_char_id'  => 100,
            'target_char_id'  => 200,
            'action'          => 'transfer',
            'brokered'        => true,
        ]);
    }

    public function test_transfer_throws_when_transfers_disabled(): void
    {
        StorageSetting::create([
            'key_name'   => 'transfers_enabled',
            'value'      => '0',
            'updated_at' => time(),
            'updated_by' => 1,
        ]);

        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 10);

        $this->expectException(TransfersLockedException::class);
        $this->service->transfer(100, 200, $itemType->id, 1, 1);
    }

    public function test_transfer_throws_when_setting_missing(): void
    {
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 10);

        $this->expectException(TransfersLockedException::class);
        $this->service->transfer(100, 200, $itemType->id, 1, 1);
    }

    public function test_transfer_throws_on_insufficient_source_quantity(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 3);

        $this->expectException(\DomainException::class);
        $this->service->transfer(100, 200, $itemType->id, 5, 1);
    }

    public function test_transfer_throws_on_nonexistent_source_inventory(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->service->transfer(100, 200, $itemType->id, 1, 1);
    }

    public function test_transfer_throws_when_receiver_cap_exceeded(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType(['max_quantity' => 10]);
        $this->seedInventory(100, $itemType->id, 10);
        $this->seedInventory(200, $itemType->id, 8);

        $this->expectException(\DomainException::class);
        $this->service->transfer(100, 200, $itemType->id, 5, 1);
    }

    public function test_transfer_allows_exactly_at_receiver_cap(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType(['max_quantity' => 10]);
        $this->seedInventory(100, $itemType->id, 10);
        $this->seedInventory(200, $itemType->id, 7);

        $result = $this->service->transfer(100, 200, $itemType->id, 3, 1);

        $this->assertEquals(7, $result['source']->quantity);
        $this->assertEquals(10, $result['target']->quantity);
    }

    public function test_transfer_rolls_back_on_cap_violation(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType(['max_quantity' => 10]);
        $this->seedInventory(100, $itemType->id, 10);
        $this->seedInventory(200, $itemType->id, 8);

        try {
            $this->service->transfer(100, 200, $itemType->id, 5, 1);
        } catch (\DomainException) {
            // expected
        }

        // Source should be unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 10,
        ]);

        // Target should be unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 8,
        ]);

        // No transfer log should exist
        $this->assertDatabaseMissing('ecc_storage_log', [
            'item_type_id' => $itemType->id,
            'action'       => 'transfer',
        ]);
    }

    public function test_brokered_transfer_throws_on_insufficient_sonuren(): void
    {
        $this->enableTransfers();
        $this->seedBrokerFee();
        $sonuren = $this->createSonurenType();
        $itemType = $this->createItemType();

        // Only 10 Sonuren — not enough for the 20 fee
        $this->seedInventory(100, $sonuren->id, 10);
        $this->seedInventory(100, $itemType->id, 5);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient Sonuren for broker fee');
        $this->service->transfer(100, 200, $itemType->id, 2, 1, true);
    }

    public function test_brokered_transfer_throws_when_no_sonuren_inventory(): void
    {
        $this->enableTransfers();
        $this->seedBrokerFee();
        $this->createSonurenType();
        $itemType = $this->createItemType();

        // Source has items but zero Sonuren inventory
        $this->seedInventory(100, $itemType->id, 5);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient Sonuren for broker fee');
        $this->service->transfer(100, 200, $itemType->id, 2, 1, true);
    }

    public function test_brokered_transfer_rolls_back_on_insufficient_sonuren(): void
    {
        $this->enableTransfers();
        $this->seedBrokerFee();
        $sonuren = $this->createSonurenType();
        $itemType = $this->createItemType();

        $this->seedInventory(100, $sonuren->id, 10);
        $this->seedInventory(100, $itemType->id, 5);

        try {
            $this->service->transfer(100, 200, $itemType->id, 2, 1, true);
        } catch (\DomainException) {
            // expected
        }

        // Sonuren should be unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $sonuren->id,
            'quantity'     => 10,
        ]);

        // Items should be unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ]);

        // No logs
        $this->assertDatabaseMissing('ecc_storage_log', [
            'action' => 'broker_fee',
        ]);
        $this->assertDatabaseMissing('ecc_storage_log', [
            'action' => 'transfer',
        ]);
    }

    public function test_transfer_throws_on_self_transfer(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Source and target character must be different');
        $this->service->transfer(100, 100, 1, 5, 1);
    }

    public function test_transfer_throws_on_zero_quantity(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transfer quantity must be a positive integer');
        $this->service->transfer(100, 200, 1, 0, 1);
    }

    public function test_transfer_throws_on_negative_quantity(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Transfer quantity must be a positive integer');
        $this->service->transfer(100, 200, 1, -5, 1);
    }

    public function test_transfer_works_with_reversed_character_ids(): void
    {
        $this->enableTransfers();
        $itemType = $this->createItemType();
        $this->seedInventory(200, $itemType->id, 10);
        $this->seedInventory(100, $itemType->id, 5);

        // Source (200) > target (100) — exercises the min/max lock ordering swap
        $result = $this->service->transfer(200, 100, $itemType->id, 3, 1, false, 'reversed');

        $this->assertEquals(7, $result['source']->quantity);
        $this->assertEquals(8, $result['target']->quantity);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 7,
        ]);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 8,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'source_char_id'  => 200,
            'target_char_id'  => 100,
            'action'          => 'transfer',
        ]);
    }
}
