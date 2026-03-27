<?php

namespace Tests\Unit\Services;

use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLog;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for InventoryService covering mint, burn, adjust, and bulk
 * operations including cap enforcement, transactional rollback, and audit logging.
 */
class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
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

    public function test_mint_creates_inventory_and_log(): void
    {
        $itemType = $this->createItemType();

        $inventory = $this->service->mint(100, $itemType->id, 5, 1, 'initial grant');

        $this->assertEquals(5, $inventory->quantity);
        $this->assertEquals(100, $inventory->character_id);
        $this->assertEquals($itemType->id, $inventory->item_type_id);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $itemType->id,
            'quantity'        => 5,
            'target_char_id'  => 100,
            'source_char_id'  => null,
            'action'          => 'mint',
            'note'            => 'initial grant',
        ]);
    }

    public function test_mint_increments_existing_inventory(): void
    {
        $itemType = $this->createItemType();

        $this->service->mint(100, $itemType->id, 3, 1);
        $inventory = $this->service->mint(100, $itemType->id, 7, 1);

        $this->assertEquals(10, $inventory->quantity);
        $this->assertCount(2, StorageLog::where('item_type_id', $itemType->id)->get());
    }

    public function test_mint_throws_when_max_quantity_exceeded(): void
    {
        $itemType = $this->createItemType(['max_quantity' => 10]);

        $this->expectException(\DomainException::class);
        $this->service->mint(100, $itemType->id, 11, 1);

        $this->assertDatabaseMissing('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
        ]);
    }

    public function test_mint_allows_exactly_at_max_quantity(): void
    {
        $itemType = $this->createItemType(['max_quantity' => 10]);

        $inventory = $this->service->mint(100, $itemType->id, 10, 1);

        $this->assertEquals(10, $inventory->quantity);
    }

    public function test_mint_with_null_max_quantity_has_no_cap(): void
    {
        $itemType = $this->createItemType(['max_quantity' => null]);

        $inventory = $this->service->mint(100, $itemType->id, 999999, 1);

        $this->assertEquals(999999, $inventory->quantity);
    }

    public function test_burn_decrements_and_creates_log(): void
    {
        $itemType = $this->createItemType();
        $this->service->mint(100, $itemType->id, 10, 1);

        $inventory = $this->service->burn(100, $itemType->id, 3, 1, 'consumed');

        $this->assertEquals(7, $inventory->quantity);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'    => $itemType->id,
            'quantity'        => 3,
            'source_char_id'  => 100,
            'target_char_id'  => null,
            'action'          => 'burn',
            'note'            => 'consumed',
        ]);
    }

    public function test_burn_throws_on_insufficient_quantity(): void
    {
        $itemType = $this->createItemType();
        $this->service->mint(100, $itemType->id, 5, 1);

        $this->expectException(\DomainException::class);
        $this->service->burn(100, $itemType->id, 10, 1);
    }

    public function test_burn_throws_on_nonexistent_inventory(): void
    {
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->service->burn(100, $itemType->id, 1, 1);
    }

    public function test_adjust_positive_with_cap_check(): void
    {
        $itemType = $this->createItemType(['max_quantity' => 20]);
        $inventory = $this->service->mint(100, $itemType->id, 15, 1);

        // Adjust up to exactly the cap — should succeed
        $adjusted = $this->service->adjust($inventory->id, 5, 1, 'top up');
        $this->assertEquals(20, $adjusted->quantity);

        // Adjust over the cap — should throw
        $this->expectException(\DomainException::class);
        $this->service->adjust($inventory->id, 1, 1);
    }

    public function test_adjust_negative_with_sufficiency_check(): void
    {
        $itemType = $this->createItemType();
        $inventory = $this->service->mint(100, $itemType->id, 10, 1);

        // Partial removal — should succeed
        $adjusted = $this->service->adjust($inventory->id, -4, 1);
        $this->assertEquals(6, $adjusted->quantity);

        // Overdraw — should throw
        $this->expectException(\DomainException::class);
        $this->service->adjust($inventory->id, -7, 1);
    }

    public function test_bulk_all_succeed(): void
    {
        $itemType = $this->createItemType();

        $result = $this->service->bulk($itemType->id, 5, [101, 102, 103], 1, 'bulk grant');

        $this->assertCount(3, $result['succeeded']);
        $this->assertCount(0, $result['failed']);

        foreach ($result['succeeded'] as $entry) {
            $this->assertEquals(5, $entry['new_quantity']);
        }
    }

    public function test_bulk_partial_failure(): void
    {
        $itemType = $this->createItemType(['max_quantity' => 5]);

        // Pre-fill one character to the cap
        $this->service->mint(101, $itemType->id, 5, 1);

        $result = $this->service->bulk($itemType->id, 3, [101, 102, 103], 1);

        // 101 should fail (already at cap), 102 and 103 should succeed
        $this->assertCount(2, $result['succeeded']);
        $this->assertCount(1, $result['failed']);
        $this->assertEquals(101, $result['failed'][0]['character_id']);
    }

    public function test_mint_transaction_rolls_back_on_cap_exceeded(): void
    {
        $itemType = $this->createItemType(['max_quantity' => 5]);

        try {
            $this->service->mint(100, $itemType->id, 10, 1);
        } catch (\DomainException) {
            // expected
        }

        $this->assertDatabaseMissing('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
        ]);

        $this->assertDatabaseMissing('ecc_storage_log', [
            'item_type_id'   => $itemType->id,
            'target_char_id' => 100,
        ]);
    }
}
