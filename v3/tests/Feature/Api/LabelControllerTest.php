<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageCategory;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLabelToken;
use App\Models\StorageLabelTokenItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for LabelController covering get by token, get unclaimed,
 * create mint/burn, claim happy path, double claim, expired, and auth.
 */
class LabelControllerTest extends TestCase
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
     * Create a label token with one item line.
     */
    private function createLabelToken(int $itemTypeId, array $overrides = []): StorageLabelToken
    {
        $token = StorageLabelToken::create(array_merge([
            'token'          => fake()->uuid(),
            'note'           => 'test label',
            'source'         => 'mint',
            'source_char_id' => null,
            'created_by'     => 1,
            'created_at'     => time(),
        ], $overrides));

        StorageLabelTokenItem::create([
            'label_token_id' => $token->id,
            'item_type_id'   => $itemTypeId,
            'quantity'       => 5,
        ]);

        return $token;
    }

    // ── GET by token ────────────────────────────────────────────────────

    public function test_index_by_token_returns_single_label(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $itemType = $this->createItemType();
        $label = $this->createLabelToken($itemType->id);

        $this->withToken($auth['token'])
            ->getJson("/api/v3/storage/labels?token={$label->token}")
            ->assertOk()
            ->assertJsonPath('data.token', $label->token);
    }

    public function test_index_by_token_returns_404_when_not_found(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/labels?token=00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    // ── GET unclaimed ───────────────────────────────────────────────────

    public function test_index_unclaimed_returns_unclaimed_tokens(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $itemType = $this->createItemType();
        $this->createLabelToken($itemType->id);
        $this->createLabelToken($itemType->id, ['claimed_by' => 100, 'claimed_at' => time()]);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/labels?unclaimed=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── POST create (mint source) ───────────────────────────────────────

    public function test_store_creates_mint_label(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType();

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels', [
                'items' => [
                    ['item_type_id' => $itemType->id, 'quantity' => 3],
                ],
                'source'   => 'mint',
                'actor_id' => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.source', 'mint')
            ->assertJsonCount(1, 'data.items');

        $this->assertDatabaseCount('ecc_storage_label_tokens', 1);
        $this->assertDatabaseCount('ecc_storage_label_token_items', 1);
    }

    // ── POST create (burn source) ───────────────────────────────────────

    public function test_store_creates_burn_label_and_deducts_inventory(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $itemType = $this->createItemType();

        StorageInventory::create([
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 10,
            'updated_at'   => time(),
        ]);

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels', [
                'items' => [
                    ['item_type_id' => $itemType->id, 'quantity' => 3],
                ],
                'source'         => 'burn',
                'source_char_id' => 100,
                'actor_id'       => 1,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.source', 'burn');

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 100,
            'item_type_id' => $itemType->id,
            'quantity'     => 7,
        ]);
    }

    public function test_store_requires_admin_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels', [])
            ->assertForbidden();
    }

    // ── POST claim ──────────────────────────────────────────────────────

    public function test_claim_mints_items_to_character(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();
        $label = $this->createLabelToken($itemType->id);

        $response = $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels/claim', [
                'token'        => $label->token,
                'character_id' => 200,
                'actor_id'     => 1,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.claimed_by', 200);

        $this->assertDatabaseHas('ecc_storage_inventory', [
            'character_id' => 200,
            'item_type_id' => $itemType->id,
            'quantity'     => 5,
        ]);

        $this->assertDatabaseHas('ecc_storage_log', [
            'action'         => 'label_claim',
            'target_char_id' => 200,
        ]);
    }

    public function test_claim_double_claim_returns_422(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();
        $label = $this->createLabelToken($itemType->id, [
            'claimed_by' => 100,
            'claimed_at' => time(),
        ]);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels/claim', [
                'token'        => $label->token,
                'character_id' => 200,
                'actor_id'     => 1,
            ])
            ->assertUnprocessable();
    }

    public function test_claim_expired_token_returns_422(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);
        $itemType = $this->createItemType();
        $label = $this->createLabelToken($itemType->id, [
            'expires_at' => time() - 3600,
        ]);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels/claim', [
                'token'        => $label->token,
                'character_id' => 200,
                'actor_id'     => 1,
            ])
            ->assertUnprocessable();
    }

    public function test_claim_requires_write_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);

        $this->withToken($auth['token'])
            ->postJson('/api/v3/storage/labels/claim', [])
            ->assertForbidden();
    }

    // ── Auth ────────────────────────────────────────────────────────────

    public function test_labels_require_authentication(): void
    {
        $this->getJson('/api/v3/storage/labels')
            ->assertUnauthorized();
    }
}
