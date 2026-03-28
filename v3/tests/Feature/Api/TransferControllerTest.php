<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TransferController covering happy path, brokered fees,
 * insufficient quantity, transfers locked, receiver cap, and auth enforcement.
 */
class TransferControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an ApiConsumer with a Sanctum token bearing the given abilities.
     *
     * @param  string[] $abilities
     * @return array{consumer: ApiConsumer, token: string}
     */
    private function createConsumerWithToken(array $abilities): array
    {
        $consumer = ApiConsumer::create(['name' => 'test-consumer', 'created_at' => time()]);
        $token = $consumer->createToken('test', $abilities)->plainTextToken;

        return ['consumer' => $consumer, 'token' => $token];
    }

    /**
     * Seed the settings required for transfer operations.
     */
    private function seedSettings(): void
    {
        StorageSetting::firstOrCreate(
            ['key_name' => 'transfers_enabled'],
            ['value' => '1', 'updated_at' => time(), 'updated_by' => 0]
        );
        StorageSetting::firstOrCreate(
            ['key_name' => 'broker_fee_sonuren'],
            ['value' => '20', 'updated_at' => time(), 'updated_by' => 0]
        );
    }

    /**
     * Create an item type with a category.
     */
    private function createItemType(array $overrides = []): StorageItemType
    {
        $category = StorageCategory::firstOrCreate(
            ['name' => 'Test Category'],
            ['created_at' => time(), 'created_by' => 1]
        );

        return StorageItemType::create(array_merge([
            'name'        => 'Item-' . uniqid(),
            'category_id' => $category->id,
            'stackable'   => true,
            'is_system'   => false,
            'created_at'  => time(),
            'created_by'  => 1,
            'updated_at'  => time(),
        ], $overrides));
    }

    /**
     * Give a character inventory of a specific item type.
     */
    private function giveInventory(int $charId, int $itemTypeId, int $quantity): StorageInventory
    {
        return StorageInventory::create([
            'character_id' => $charId,
            'item_type_id' => $itemTypeId,
            'quantity'     => $quantity,
            'updated_at'   => time(),
        ]);
    }

    // ── Happy path ──────────────────────────────────────────────────────

    public function test_transfer_moves_items_between_characters(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $this->seedSettings();
        $itemType = $this->createItemType();
        $this->giveInventory(1, $itemType->id, 10);

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [
                'source_char_id' => 1,
                'target_char_id' => 2,
                'item_type_id'   => $itemType->id,
                'quantity'       => 3,
                'actor_id'       => 1,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 1,
            'item_type_id' => $itemType->id,
            'quantity'     => 7,
        ]);
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 2,
            'item_type_id' => $itemType->id,
            'quantity'     => 3,
        ]);
        $this->assertDatabaseHas('ecc_storage_log', [
            'action'         => 'transfer',
            'source_char_id' => 1,
            'target_char_id' => 2,
            'quantity'       => 3,
        ]);
    }

    // ── Brokered ────────────────────────────────────────────────────────

    public function test_brokered_transfer_deducts_fee(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $this->seedSettings();
        $itemType = $this->createItemType();
        $sonuren = $this->createItemType(['name' => 'Sonuren', 'is_system' => true]);

        $this->giveInventory(1, $itemType->id, 10);
        $this->giveInventory(1, $sonuren->id, 50);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [
                'source_char_id' => 1,
                'target_char_id' => 2,
                'item_type_id'   => $itemType->id,
                'quantity'       => 3,
                'brokered'       => true,
                'actor_id'       => 1,
            ])
            ->assertCreated();

        // Sonuren balance should be 50 - 20 = 30
        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 1,
            'item_type_id' => $sonuren->id,
            'quantity'     => 30,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'action'   => 'broker_fee',
            'brokered' => 1,
        ]);
    }

    public function test_brokered_transfer_fails_with_insufficient_sonuren(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $this->seedSettings();
        $itemType = $this->createItemType();
        $sonuren = $this->createItemType(['name' => 'Sonuren', 'is_system' => true]);

        $this->giveInventory(1, $itemType->id, 10);
        $this->giveInventory(1, $sonuren->id, 10); // less than 20 fee

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [
                'source_char_id' => 1,
                'target_char_id' => 2,
                'item_type_id'   => $itemType->id,
                'quantity'       => 3,
                'brokered'       => true,
                'actor_id'       => 1,
            ])
            ->assertUnprocessable();
    }

    // ── Insufficient quantity ───────────────────────────────────────────

    public function test_transfer_fails_with_insufficient_quantity(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $this->seedSettings();
        $itemType = $this->createItemType();
        $this->giveInventory(1, $itemType->id, 2);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [
                'source_char_id' => 1,
                'target_char_id' => 2,
                'item_type_id'   => $itemType->id,
                'quantity'       => 5,
                'actor_id'       => 1,
            ])
            ->assertUnprocessable();
    }

    // ── Transfers locked ────────────────────────────────────────────────

    public function test_transfer_fails_when_transfers_disabled(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $this->seedSettings();
        StorageSetting::where('key_name', 'transfers_enabled')->update(['value' => '0']);

        $itemType = $this->createItemType();
        $this->giveInventory(1, $itemType->id, 10);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [
                'source_char_id' => 1,
                'target_char_id' => 2,
                'item_type_id'   => $itemType->id,
                'quantity'       => 3,
                'actor_id'       => 1,
            ])
            ->assertStatus(423);
    }

    // ── Receiver cap ────────────────────────────────────────────────────

    public function test_transfer_fails_when_receiver_cap_exceeded(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $this->seedSettings();
        $itemType = $this->createItemType(['max_quantity' => 5]);
        $this->giveInventory(1, $itemType->id, 10);
        $this->giveInventory(2, $itemType->id, 4);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [
                'source_char_id' => 1,
                'target_char_id' => 2,
                'item_type_id'   => $itemType->id,
                'quantity'       => 3,
                'actor_id'       => 1,
            ])
            ->assertUnprocessable();
    }

    // ── Auth ────────────────────────────────────────────────────────────

    public function test_transfer_requires_authentication(): void
    {
        $this->postJson('/api/v3/storage/transfer', [])
            ->assertUnauthorized();
    }

    public function test_transfer_requires_write_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/transfer', [])
            ->assertForbidden();
    }
}
