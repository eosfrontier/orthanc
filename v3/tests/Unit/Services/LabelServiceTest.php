<?php

namespace Tests\Unit\Services;

use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLabelToken;
use App\Models\StorageLabelTokenItem;
use App\Models\StorageLog;
use App\Services\LabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for LabelService covering multi-item mint/burn token creation,
 * lookup, unclaimed listing, claim flow, expiry, cap enforcement, and
 * transactional rollback.
 */
class LabelServiceTest extends TestCase
{
    use RefreshDatabase;

    private LabelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LabelService();
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

    // ---------------------------------------------------------------
    // create() — mint source, single item
    // ---------------------------------------------------------------

    public function test_create_mint_single_item_inserts_token_and_item(): void
    {
        $itemType = $this->createItemType();

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 5],
            ],
            'note' => 'free sample',
        ], 1);

        $this->assertInstanceOf(StorageLabelToken::class, $token);
        $this->assertEquals(36, strlen($token->token));
        $this->assertEquals('mint', $token->source);
        $this->assertEquals('free sample', $token->note);
        $this->assertNull($token->source_char_id);
        $this->assertEquals(1, $token->created_by);
        $this->assertNull($token->claimed_by);

        $this->assertCount(1, $token->items);
        $this->assertEquals($itemType->id, $token->items[0]->item_type_id);
        $this->assertEquals(5, $token->items[0]->quantity);

        $this->assertDatabaseHas('ecc_storage_label_token_items', [
            'label_token_id' => $token->id,
            'item_type_id'   => $itemType->id,
            'quantity'       => 5,
        ]);
    }

    // ---------------------------------------------------------------
    // create() — mint source, multiple items
    // ---------------------------------------------------------------

    public function test_create_mint_multiple_items_inserts_all_lines(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B']);

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemA->id, 'quantity' => 3],
                ['item_type_id' => $itemB->id, 'quantity' => 7],
            ],
        ], 1);

        $this->assertCount(2, $token->items);

        $this->assertDatabaseHas('ecc_storage_label_token_items', [
            'label_token_id' => $token->id,
            'item_type_id'   => $itemA->id,
            'quantity'       => 3,
        ]);

        $this->assertDatabaseHas('ecc_storage_label_token_items', [
            'label_token_id' => $token->id,
            'item_type_id'   => $itemB->id,
            'quantity'       => 7,
        ]);
    }

    public function test_create_mint_does_not_write_log(): void
    {
        $itemType = $this->createItemType();

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 1],
            ],
        ], 1);

        $this->assertDatabaseMissing('ecc_storage_log', [
            'item_type_id' => $itemType->id,
        ]);
    }

    public function test_create_mint_does_not_change_inventory(): void
    {
        $itemType = $this->createItemType();

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 3],
            ],
        ], 1);

        $this->assertEquals(0, StorageInventory::count());
    }

    public function test_create_throws_on_empty_items(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('At least one item is required');

        $this->service->create(['items' => []], 1);
    }

    public function test_create_throws_on_zero_quantity(): void
    {
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token quantity must be a positive integer');

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 0],
            ],
        ], 1);
    }

    public function test_create_throws_on_negative_quantity(): void
    {
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token quantity must be a positive integer');

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => -1],
            ],
        ], 1);
    }

    // ---------------------------------------------------------------
    // create() — burn source
    // ---------------------------------------------------------------

    public function test_create_burn_decrements_inventory_and_inserts_token(): void
    {
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 10);

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 3],
            ],
            'source'         => 'burn',
            'source_char_id' => 100,
        ], 1);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 7,
        ]);

        $this->assertCount(1, $token->items);
        $this->assertDatabaseHas('ecc_storage_label_token_items', [
            'label_token_id' => $token->id,
            'item_type_id'   => $itemType->id,
            'quantity'       => 3,
        ]);

        $this->assertEquals('burn', $token->source);
        $this->assertEquals(100, $token->source_char_id);
    }

    public function test_create_burn_multiple_items_decrements_all(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B']);
        $this->seedInventory(100, $itemA->id, 10);
        $this->seedInventory(100, $itemB->id, 20);

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemA->id, 'quantity' => 2],
                ['item_type_id' => $itemB->id, 'quantity' => 5],
            ],
            'source'         => 'burn',
            'source_char_id' => 100,
        ], 1);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemA->id,
            'quantity'     => 8,
        ]);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemB->id,
            'quantity'     => 15,
        ]);

        $this->assertCount(2, $token->items);
    }

    public function test_create_burn_writes_label_burn_log_per_item(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B']);
        $this->seedInventory(100, $itemA->id, 10);
        $this->seedInventory(100, $itemB->id, 10);

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemA->id, 'quantity' => 2],
                ['item_type_id' => $itemB->id, 'quantity' => 3],
            ],
            'source'         => 'burn',
            'source_char_id' => 100,
        ], 1);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemA->id,
            'quantity'       => 2,
            'source_char_id' => 100,
            'action'         => 'label_burn',
            'actor_id'       => 1,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemB->id,
            'quantity'       => 3,
            'source_char_id' => 100,
            'action'         => 'label_burn',
            'actor_id'       => 1,
        ]);
    }

    public function test_create_burn_throws_on_insufficient_inventory(): void
    {
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 2);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Insufficient quantity');

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 5],
            ],
            'source'         => 'burn',
            'source_char_id' => 100,
        ], 1);
    }

    public function test_create_burn_throws_without_source_char_id(): void
    {
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('source_char_id is required');

        $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 1],
            ],
            'source' => 'burn',
        ], 1);
    }

    // ---------------------------------------------------------------
    // create() — rollback
    // ---------------------------------------------------------------

    public function test_create_burn_rolls_back_on_failure(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B']);
        $this->seedInventory(100, $itemA->id, 10);
        $this->seedInventory(100, $itemB->id, 1); // not enough for item B

        try {
            $this->service->create([
                'items' => [
                    ['item_type_id' => $itemA->id, 'quantity' => 3],
                    ['item_type_id' => $itemB->id, 'quantity' => 5],
                ],
                'source'         => 'burn',
                'source_char_id' => 100,
            ], 1);
            $this->fail('Expected DomainException was not thrown.');
        } catch (\DomainException) {
            // expected
        }

        // Both inventories unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemA->id,
            'quantity'     => 10,
        ]);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemB->id,
            'quantity'     => 1,
        ]);

        // No token created
        $this->assertEquals(0, StorageLabelToken::count());
        $this->assertEquals(0, StorageLabelTokenItem::count());
    }

    // ---------------------------------------------------------------
    // getByToken()
    // ---------------------------------------------------------------

    public function test_get_by_token_returns_token_with_items(): void
    {
        $itemType = $this->createItemType();

        $created = $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 1],
            ],
        ], 1);

        $found = $this->service->getByToken($created->token);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertTrue($found->relationLoaded('items'));
        $this->assertCount(1, $found->items);
        $this->assertTrue($found->items[0]->relationLoaded('itemType'));
        $this->assertEquals($itemType->id, $found->items[0]->itemType->id);
    }

    public function test_get_by_token_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->getByToken('00000000-0000-0000-0000-000000000000'));
    }

    // ---------------------------------------------------------------
    // getUnclaimed()
    // ---------------------------------------------------------------

    public function test_get_unclaimed_returns_only_unclaimed_tokens(): void
    {
        $itemType = $this->createItemType();

        $token1 = $this->service->create(['items' => [['item_type_id' => $itemType->id, 'quantity' => 1]]], 1);
        $token2 = $this->service->create(['items' => [['item_type_id' => $itemType->id, 'quantity' => 1]]], 1);
        $token3 = $this->service->create(['items' => [['item_type_id' => $itemType->id, 'quantity' => 1]]], 1);

        // Claim one
        $token2->claimed_by = 100;
        $token2->claimed_at = time();
        $token2->save();

        $unclaimed = $this->service->getUnclaimed();

        $this->assertCount(2, $unclaimed);
        $this->assertFalse($unclaimed->contains('id', $token2->id));
    }

    public function test_get_unclaimed_ordered_by_created_at_desc(): void
    {
        $itemType = $this->createItemType();

        $older = StorageLabelToken::create([
            'token'      => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'source'     => 'mint',
            'created_by' => 1,
            'created_at' => 1000,
        ]);
        StorageLabelTokenItem::create([
            'label_token_id' => $older->id,
            'item_type_id'   => $itemType->id,
            'quantity'       => 1,
        ]);

        $newer = StorageLabelToken::create([
            'token'      => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'source'     => 'mint',
            'created_by' => 1,
            'created_at' => 2000,
        ]);
        StorageLabelTokenItem::create([
            'label_token_id' => $newer->id,
            'item_type_id'   => $itemType->id,
            'quantity'       => 1,
        ]);

        $unclaimed = $this->service->getUnclaimed();

        $this->assertEquals($newer->id, $unclaimed->first()->id);
        $this->assertEquals($older->id, $unclaimed->last()->id);
    }

    // ---------------------------------------------------------------
    // claim() — single item
    // ---------------------------------------------------------------

    public function test_claim_mints_items_and_marks_token_claimed(): void
    {
        $itemType = $this->createItemType();

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 5],
            ],
        ], 1);

        $claimed = $this->service->claim($token->token, 200, 1);

        $this->assertEquals(200, $claimed->claimed_by);
        $this->assertNotNull($claimed->claimed_at);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ]);
    }

    // ---------------------------------------------------------------
    // claim() — multiple items
    // ---------------------------------------------------------------

    public function test_claim_multiple_items_mints_all_into_inventory(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B']);

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemA->id, 'quantity' => 3],
                ['item_type_id' => $itemB->id, 'quantity' => 7],
            ],
        ], 1);

        $this->service->claim($token->token, 200, 1);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemA->id,
            'quantity'     => 3,
        ]);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemB->id,
            'quantity'     => 7,
        ]);
    }

    public function test_claim_writes_label_claim_log_per_item(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B']);

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemA->id, 'quantity' => 3],
                ['item_type_id' => $itemB->id, 'quantity' => 2],
            ],
            'note' => 'reward',
        ], 1);

        $this->service->claim($token->token, 200, 1);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemA->id,
            'quantity'       => 3,
            'target_char_id' => 200,
            'action'         => 'label_claim',
            'note'           => 'reward',
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemB->id,
            'quantity'       => 2,
            'target_char_id' => 200,
            'action'         => 'label_claim',
            'note'           => 'reward',
        ]);
    }

    public function test_claim_throws_for_nonexistent_token(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Label token not found');

        $this->service->claim('00000000-0000-0000-0000-000000000000', 200, 1);
    }

    public function test_claim_throws_for_already_claimed_token(): void
    {
        $itemType = $this->createItemType();

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemType->id, 'quantity' => 1],
            ],
        ], 1);

        $this->service->claim($token->token, 200, 1);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already been claimed');

        $this->service->claim($token->token, 300, 1);
    }

    public function test_claim_throws_for_expired_token(): void
    {
        $itemType = $this->createItemType();

        $token = StorageLabelToken::create([
            'token'      => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'source'     => 'mint',
            'created_by' => 1,
            'created_at' => time(),
            'expires_at' => time() - 3600,
        ]);
        StorageLabelTokenItem::create([
            'label_token_id' => $token->id,
            'item_type_id'   => $itemType->id,
            'quantity'       => 1,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');

        $this->service->claim($token->token, 200, 1);
    }

    public function test_claim_rolls_back_on_max_quantity_exceeded(): void
    {
        $itemA = $this->createItemType(['name' => 'Item A']);
        $itemB = $this->createItemType(['name' => 'Item B', 'max_quantity' => 10]);
        $this->seedInventory(200, $itemB->id, 8);

        $token = $this->service->create([
            'items' => [
                ['item_type_id' => $itemA->id, 'quantity' => 2],
                ['item_type_id' => $itemB->id, 'quantity' => 5], // would exceed cap of 10
            ],
        ], 1);

        try {
            $this->service->claim($token->token, 200, 1);
            $this->fail('Expected DomainException was not thrown.');
        } catch (\DomainException) {
            // expected
        }

        // No inventory created for item A (rolled back)
        $this->assertDatabaseMissing('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemA->id,
        ]);

        // Item B inventory unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemB->id,
            'quantity'     => 8,
        ]);

        // Token still unclaimed
        $token->refresh();
        $this->assertNull($token->claimed_by);

        // No claim logs
        $this->assertDatabaseMissing('ecc_storage_log', [
            'action' => 'label_claim',
        ]);
    }
}
