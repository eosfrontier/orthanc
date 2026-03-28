<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageCategory;
use App\Models\StorageItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for CategoryController covering CRUD, is_system guards,
 * delete-with-active-item-types check, and auth/ability enforcement.
 */
class CategoryControllerTest extends TestCase
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

    // ── Index ───────────────────────────────────────────────────────────

    public function test_index_returns_active_categories(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $this->createCategory(['name' => 'Weapons']);
        $this->createCategory(['name' => 'Deleted', 'deleted_at' => time()]);

        $response = $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Weapons');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v3/storage/categories')
            ->assertUnauthorized();
    }

    public function test_index_requires_read_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/categories')
            ->assertForbidden();
    }

    // ── Show ────────────────────────────────────────────────────────────

    public function test_show_returns_single_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $category = $this->createCategory(['name' => 'Resources']);

        $this->withToken($auth['token'])
            ->getJson("/api/v3/storage/categories/{$category->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Resources');
    }

    public function test_show_returns_404_for_deleted_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $category = $this->createCategory(['deleted_at' => time()]);

        $this->withToken($auth['token'])
            ->getJson("/api/v3/storage/categories/{$category->id}")
            ->assertNotFound();
    }

    // ── Store ───────────────────────────────────────────────────────────

    public function test_store_creates_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/categories', ['name' => 'Armor']);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Armor');

        $this->assertDatabaseHas('ecc_storage_categories', ['name' => 'Armor']);
    }

    public function test_store_requires_admin_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/categories', ['name' => 'Armor'])
            ->assertForbidden();
    }

    public function test_store_validates_unique_name(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $this->createCategory(['name' => 'Armor']);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/categories', ['name' => 'Armor'])
            ->assertUnprocessable();
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function test_update_renames_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory(['name' => 'Old Name']);

        $this->withToken($auth['token'])
            ->putJson("/api/v3/storage/categories/{$category->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_update_rejects_system_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory(['name' => 'Currency', 'is_system' => true]);

        $this->withToken($auth['token'])
            ->putJson("/api/v3/storage/categories/{$category->id}", ['name' => 'Money'])
            ->assertForbidden();
    }

    // ── Destroy ─────────────────────────────────────────────────────────

    public function test_destroy_soft_deletes_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory();

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/categories/{$category->id}")
            ->assertNoContent();

        $this->assertNotNull(StorageCategory::find($category->id)->deleted_at);
    }

    public function test_destroy_rejects_system_category(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory(['is_system' => true]);

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/categories/{$category->id}")
            ->assertForbidden();
    }

    public function test_destroy_rejects_category_with_active_item_types(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory();

        StorageItemType::create([
            'name'        => 'Sword',
            'category_id' => $category->id,
            'stackable'   => true,
            'is_system'   => false,
            'created_at'  => time(),
            'created_by'  => 1,
            'updated_at'  => time(),
        ]);

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/categories/{$category->id}")
            ->assertStatus(409);
    }

    public function test_destroy_allows_category_with_only_deleted_item_types(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $category = $this->createCategory();

        StorageItemType::create([
            'name'        => 'Old Sword',
            'category_id' => $category->id,
            'stackable'   => true,
            'is_system'   => false,
            'created_at'  => time(),
            'created_by'  => 1,
            'updated_at'  => time(),
            'deleted_at'  => time(),
        ]);

        $this->withToken($auth['token'])
            ->deleteJson("/api/v3/storage/categories/{$category->id}")
            ->assertNoContent();
    }
}
