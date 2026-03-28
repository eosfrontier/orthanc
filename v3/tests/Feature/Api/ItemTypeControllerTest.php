<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageCategory;
use App\Models\StorageItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ItemTypeController covering CRUD, is_system guards,
 * category_name in response, and auth/ability enforcement.
 */
class ItemTypeControllerTest extends TestCase
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
     * Create a category with sensible defaults.
     */
    private function createCategory(array $overrides = []): StorageCategory
    {
        return StorageCategory::create(array_merge([
            'name'       => 'Test Category',
            'created_at' => time(),
            'created_by' => 1,
        ], $overrides));
    }

    /**
     * Create an item type with sensible defaults.
     */
    private function createItemType(array $overrides = []): StorageItemType
    {
        if (! isset($overrides['category_id'])) {
            $overrides['category_id'] = $this->createCategory()->id;
        }

        return StorageItemType::create(array_merge([
            'name'        => 'Test Item',
            'category_id' => $overrides['category_id'],
            'stackable'   => true,
            'is_system'   => false,
            'created_at'  => time(),
            'created_by'  => 1,
            'updated_at'  => time(),
        ], $overrides));
    }

    // ── Index ───────────────────────────────────────────────────────────

    public function test_index_returns_active_item_types_with_category_name(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $category = $this->createCategory(['name' => 'Weapons']);
        $this->createItemType(['name' => 'Sword', 'category_id' => $category->id]);
        $this->createItemType(['name' => 'Deleted', 'category_id' => $category->id, 'deleted_at' => time()]);

        $response = $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/item-types');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Sword')
            ->assertJsonPath('data.0.category_name', 'Weapons');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v3/storage/item-types')
            ->assertUnauthorized();
    }

    public function test_index_requires_read_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/item-types')
            ->assertForbidden();
    }

    // ── Show ────────────────────────────────────────────────────────────

    public function test_show_returns_item_type_with_category_name(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $category = $this->createCategory(['name' => 'Resources']);
        $itemType = $this->createItemType(['name' => 'Wood', 'category_id' => $category->id]);

        $this->withToken($auth['token'])
            ->getJson("/api/v3/storage/item-types/{$itemType->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Wood')
            ->assertJsonPath('data.category_name', 'Resources');
    }

    public function test_show_returns_404_for_deleted_item_type(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $itemType = $this->createItemType(['deleted_at' => time()]);

        $this->withToken($auth['token'])
            ->getJson("/api/v3/storage/item-types/{$itemType->id}")
            ->assertNotFound();
    }

    // ── Store ───────────────────────────────────────────────────────────

    public function test_store_creates_item_type(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory();

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/item-types', [
                'name'         => 'Health Potion',
                'category_id'  => $category->id,
                'stackable'    => true,
                'max_quantity'  => 50,
                'actor_id'     => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Health Potion')
            ->assertJsonPath('data.max_quantity', 50);

        $this->assertDatabaseHas('ecc_storage_item_types', ['name' => 'Health Potion']);
    }

    public function test_store_requires_admin_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $category = $this->createCategory();

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/item-types', [
                'name'        => 'Sword',
                'category_id' => $category->id,
            ])
            ->assertForbidden();
    }

    public function test_store_validates_category_exists(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/item-types', [
                'name'        => 'Sword',
                'category_id' => 99999,
            ])
            ->assertUnprocessable();
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function test_update_modifies_item_type(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory();
        $itemType = $this->createItemType(['name' => 'Old Name', 'category_id' => $category->id]);

        $this->withToken($auth['token'])
            ->putJson("/api/v3/storage/item-types/{$itemType->id}", [
                'name'        => 'New Name',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_update_rejects_system_item_type(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory();
        $itemType = $this->createItemType([
            'name'        => 'Sonuren',
            'category_id' => $category->id,
            'is_system'   => true,
        ]);

        $this->withToken($auth['token'])
            ->putJson("/api/v3/storage/item-types/{$itemType->id}", [
                'name'        => 'Gold',
                'category_id' => $category->id,
            ])
            ->assertForbidden();
    }

    // ── Destroy ─────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_item_type(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType();

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/item-types/{$itemType->id}")
            ->assertNoContent();

        $this->assertNotNull(StorageItemType::find($itemType->id)->deleted_at);
    }

    public function test_destroy_rejects_system_item_type(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType(['is_system' => true]);

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/item-types/{$itemType->id}")
            ->assertForbidden();
    }
}
