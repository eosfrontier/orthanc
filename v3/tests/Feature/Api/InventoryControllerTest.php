<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for InventoryController covering mint, adjust, burn,
 * bulk operations (201/207/422), max_quantity cap, and auth enforcement.
 */
class InventoryControllerTest extends TestCase
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
     * Create an item type with a category for testing.
     */
    private function createItemType(array $overrides = []): StorageItemType
    {
        $category = StorageCategory::create([
            'name'       => 'Cat-' . uniqid(),
            'created_at' => time(),
            'created_by' => 1,
        ]);

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

    // ── Index ───────────────────────────────────────────────────────────

    public function test_index_returns_inventory_for_character(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $itemType = $this->createItemType();

        StorageInventory::create([
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 10,
            'updated_at'   => time(),
        ]);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/inventory?char_id=100')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.quantity', 10);
    }

    public function test_index_requires_char_id(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/inventory')
            ->assertUnprocessable();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v3/storage/inventory?char_id=1')
            ->assertUnauthorized();
    }

    // ── Store (Mint) ────────────────────────────────────────────────────

    public function test_store_mints_items(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory', [
                'character_id' => 100,
                'item_type_id' => $itemType->id,
                'quantity'     => 5,
                'actor_id'     => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.quantity', 5);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemType->id,
            'quantity'       => 5,
            'target_char_id' => 100,
            'action'         => 'mint',
        ]);
    }

    public function test_store_rejects_when_max_quantity_exceeded(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType(['max_quantity' => 10]);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory', [
                'character_id' => 100,
                'item_type_id' => $itemType->id,
                'quantity'     => 11,
                'actor_id'     => 1,
            ])
            ->assertUnprocessable();
    }

    public function test_store_requires_write_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $itemType = $this->createItemType();

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory', [
                'character_id' => 100,
                'item_type_id' => $itemType->id,
                'quantity'     => 5,
                'actor_id'     => 1,
            ])
            ->assertForbidden();
    }

    // ── Adjust ──────────────────────────────────────────────────────────

    public function test_adjust_modifies_quantity_by_delta(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();

        $inventory = StorageInventory::create([
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 10,
            'updated_at'   => time(),
        ]);

        $this->withToken($auth['token'])
            ->patchJson("/api/v3/storage/inventory/{$inventory->id}", [
                'quantity' => -3,
                'actor_id' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7);
    }

    // ── Destroy (Burn) ──────────────────────────────────────────────────

    public function test_destroy_burns_items(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();

        $inventory = StorageInventory::create([
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 10,
            'updated_at'   => time(),
        ]);

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/inventory/{$inventory->id}", [
                'quantity' => 3,
                'actor_id' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', 7);

        $this->assertDatabaseHas('ecc_storage_log', [
            'item_type_id'   => $itemType->id,
            'quantity'       => 3,
            'source_char_id' => 100,
            'action'         => 'burn',
        ]);
    }

    // ── Bulk ────────────────────────────────────────────────────────────

    public function test_bulk_all_succeed_returns_201(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType();

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory/bulk', [
                'item_type_id'  => $itemType->id,
                'quantity'      => 5,
                'character_ids' => [101, 102, 103],
                'actor_id'      => 1,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('total_attempted', 3)
            ->assertJsonPath('total_succeeded', 3)
            ->assertJsonPath('total_failed', 0);
    }

    public function test_bulk_partial_failure_returns_207(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType(['max_quantity' => 5]);

        // Pre-fill one character to the cap
        StorageInventory::create([
            'character_id' => 101,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
            'updated_at'   => time(),
        ]);

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory/bulk', [
                'item_type_id'  => $itemType->id,
                'quantity'      => 3,
                'character_ids' => [101, 102],
                'actor_id'      => 1,
            ]);

        $response->assertStatus(207)
            ->assertJsonPath('total_succeeded', 1)
            ->assertJsonPath('total_failed', 1);
    }

    public function test_bulk_all_fail_returns_422(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType(['max_quantity' => 1]);

        StorageInventory::create([
            'character_id' => 101,
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
            'updated_at'   => time(),
        ]);
        StorageInventory::create([
            'character_id' => 102,
            'item_type_id' => $itemType->id,
            'quantity'     => 1,
            'updated_at'   => time(),
        ]);

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory/bulk', [
                'item_type_id'  => $itemType->id,
                'quantity'      => 1,
                'character_ids' => [101, 102],
                'actor_id'      => 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('total_succeeded', 0)
            ->assertJsonPath('total_failed', 2);
    }

    public function test_bulk_requires_admin_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/inventory/bulk', [
                'item_type_id'  => $itemType->id,
                'quantity'      => 5,
                'character_ids' => [101],
                'actor_id'      => 1,
            ])
            ->assertForbidden();
    }
}
