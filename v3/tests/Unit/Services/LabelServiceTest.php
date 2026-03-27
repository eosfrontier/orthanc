<?php

namespace Tests\Unit\Services;

use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLabelToken;
use App\Models\StorageLog;
use App\Services\LabelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for LabelService covering mint/burn token creation, lookup,
 * unclaimed listing, claim flow, expiry, cap enforcement, and transactional rollback.
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
    // create() — mint source
    // ---------------------------------------------------------------

    public function test_create_mint_inserts_token_record(): void
    {
        $itemType = $this->createItemType();

        $token = $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
            'note'         => 'free sample',
        ], 1);

        $this->assertInstanceOf(StorageLabelToken::class, $token);
        $this->assertEquals(36, strlen($token->token));
        $this->assertEquals($itemType->id, $token->item_type_id);
        $this->assertEquals(5, $token->quantity);
        $this->assertEquals('mint', $token->source);
        $this->assertEquals('free sample', $token->note);
        $this->assertNull($token->source_char_id);
        $this->assertEquals(1, $token->created_by);
        $this->assertNull($token->claimed_by);

        $this->assertDatabaseHas('ecc_storage_label_tokens', [
            'id'           => $token->id,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
            'source'       => 'mint',
        ]);
    }

    public function test_create_mint_does_not_write_log(): void
    {
        $itemType = $this->createItemType();

        $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
        ], 1);

        $this->assertDatabaseMissing('ecc_storage_log', [
            'item_type_id' => $itemType->id,
        ]);
    }

    public function test_create_mint_does_not_change_inventory(): void
    {
        $itemType = $this->createItemType();

        $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 3,
        ], 1);

        $this->assertEquals(0, StorageInventory::count());
    }

    public function test_create_mint_throws_on_zero_quantity(): void
    {
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token quantity must be a positive integer');

        $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 0,
        ], 1);
    }

    public function test_create_mint_throws_on_negative_quantity(): void
    {
        $itemType = $this->createItemType();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token quantity must be a positive integer');

        $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => -1,
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
            'item_type_id'   => $itemType->id,
            'quantity'       => 3,
            'source'         => 'burn',
            'source_char_id' => 100,
        ], 1);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 7,
        ]);

        $this->assertDatabaseHas('ecc_storage_label_tokens', [
            'id'             => $token->id,
            'source'         => 'burn',
            'source_char_id' => 100,
            'quantity'       => 3,
        ]);
    }

    public function test_create_burn_writes_label_burn_log(): void
    {
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 10);

        $this->service->create([
            'item_type_id'   => $itemType->id,
            'quantity'       => 2,
            'source'         => 'burn',
            'source_char_id' => 100,
        ], 1);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemType->id,
            'quantity'       => 2,
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
            'item_type_id'   => $itemType->id,
            'quantity'       => 5,
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
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
            'source'       => 'burn',
        ], 1);
    }

    // ---------------------------------------------------------------
    // create() — rollback
    // ---------------------------------------------------------------

    public function test_create_burn_rolls_back_on_failure(): void
    {
        $itemType = $this->createItemType();
        $this->seedInventory(100, $itemType->id, 2);

        try {
            $this->service->create([
                'item_type_id'   => $itemType->id,
                'quantity'       => 5,
                'source'         => 'burn',
                'source_char_id' => 100,
            ], 1);
            $this->fail('Expected DomainException was not thrown.');
        } catch (\DomainException) {
            // expected
        }

        // Inventory unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 2,
        ]);

        // No token created
        $this->assertEquals(0, StorageLabelToken::count());
    }

    // ---------------------------------------------------------------
    // getByToken()
    // ---------------------------------------------------------------

    public function test_get_by_token_returns_token_with_item_type(): void
    {
        $itemType = $this->createItemType();

        $created = $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
        ], 1);

        $found = $this->service->getByToken($created->token);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertTrue($found->relationLoaded('itemType'));
        $this->assertEquals($itemType->id, $found->itemType->id);
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

        // Create 3 tokens
        $token1 = $this->service->create(['item_type_id' => $itemType->id, 'quantity' => 1], 1);
        $token2 = $this->service->create(['item_type_id' => $itemType->id, 'quantity' => 1], 1);
        $token3 = $this->service->create(['item_type_id' => $itemType->id, 'quantity' => 1], 1);

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
            'token'        => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
            'source'       => 'mint',
            'created_by'   => 1,
            'created_at'   => 1000,
        ]);

        $newer = StorageLabelToken::create([
            'token'        => 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb',
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
            'source'       => 'mint',
            'created_by'   => 1,
            'created_at'   => 2000,
        ]);

        $unclaimed = $this->service->getUnclaimed();

        $this->assertEquals($newer->id, $unclaimed->first()->id);
        $this->assertEquals($older->id, $unclaimed->last()->id);
    }

    // ---------------------------------------------------------------
    // claim()
    // ---------------------------------------------------------------

    public function test_claim_mints_items_and_marks_token_claimed(): void
    {
        $itemType = $this->createItemType();

        $token = $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ], 1);

        $inventory = $this->service->claim($token->token, 200, 1);

        $this->assertEquals(5, $inventory->quantity);
        $this->assertEquals(200, $inventory->character_id);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ]);

        $token->refresh();
        $this->assertEquals(200, $token->claimed_by);
        $this->assertNotNull($token->claimed_at);
    }

    public function test_claim_writes_label_claim_log(): void
    {
        $itemType = $this->createItemType();

        $token = $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 3,
            'note'         => 'reward',
        ], 1);

        $this->service->claim($token->token, 200, 1);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemType->id,
            'quantity'       => 3,
            'target_char_id' => 200,
            'action'         => 'label_claim',
            'actor_id'       => 1,
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
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
        ], 1);

        // Claim it once
        $this->service->claim($token->token, 200, 1);

        // Try to claim again
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already been claimed');

        $this->service->claim($token->token, 300, 1);
    }

    public function test_claim_throws_for_expired_token(): void
    {
        $itemType = $this->createItemType();

        $token = StorageLabelToken::create([
            'token'        => 'cccccccc-cccc-cccc-cccc-cccccccccccc',
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
            'source'       => 'mint',
            'created_by'   => 1,
            'created_at'   => time(),
            'expires_at'   => time() - 3600, // expired an hour ago
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('expired');

        $this->service->claim($token->token, 200, 1);
    }

    public function test_claim_rolls_back_on_max_quantity_exceeded(): void
    {
        $itemType = $this->createItemType(['max_quantity' => 10]);
        $this->seedInventory(200, $itemType->id, 8);

        $token = $this->service->create([
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ], 1);

        try {
            $this->service->claim($token->token, 200, 1);
            $this->fail('Expected DomainException was not thrown.');
        } catch (\DomainException) {
            // expected
        }

        // Inventory unchanged
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 8,
        ]);

        // Token still unclaimed
        $token->refresh();
        $this->assertNull($token->claimed_by);

        // No claim log
        $this->assertDatabaseMissing('ecc_storage_log', [
            'action' => 'label_claim',
        ]);
    }
}
