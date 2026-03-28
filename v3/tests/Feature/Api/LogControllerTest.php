<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for LogController covering char_id UNION ALL filter,
 * item_type_id filter, brokered exclusion, include_brokered, pagination, and auth.
 */
class LogControllerTest extends TestCase
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
     * Create a log entry with sensible defaults.
     */
    private function createLog(array $overrides = []): StorageLog
    {
        return StorageLog::create(array_merge([
            'item_type_id'   => 1,
            'quantity'       => 1,
            'source_char_id' => null,
            'target_char_id' => null,
            'actor_id'       => 1,
            'action'         => 'mint',
            'brokered'       => false,
            'note'           => null,
            'created_at'     => time(),
        ], $overrides));
    }

    // ── Basic listing ───────────────────────────────────────────────────

    public function test_index_returns_log_entries(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $this->createLog();

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'page', 'per_page', 'has_more']);
    }

    // ── char_id UNION ALL filter ────────────────────────────────────────

    public function test_char_id_returns_source_and_target_entries(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);

        // char 10 as source
        $this->createLog(['source_char_id' => 10, 'target_char_id' => 20, 'action' => 'transfer']);
        // char 10 as target
        $this->createLog(['source_char_id' => 30, 'target_char_id' => 10, 'action' => 'transfer']);
        // unrelated
        $this->createLog(['source_char_id' => 30, 'target_char_id' => 40, 'action' => 'transfer']);

        $response = $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log?char_id=10');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ── item_type_id filter ─────────────────────────────────────────────

    public function test_item_type_id_filter(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $this->createLog(['item_type_id' => 1]);
        $this->createLog(['item_type_id' => 2]);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log?item_type_id=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Brokered exclusion ──────────────────────────────────────────────

    public function test_brokered_entries_excluded_by_default(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $this->createLog(['brokered' => false]);
        $this->createLog(['brokered' => true]);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_include_brokered_shows_all(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $this->createLog(['brokered' => false]);
        $this->createLog(['brokered' => true]);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log?include_brokered=1')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // ── Pagination ──────────────────────────────────────────────────────

    public function test_pagination_with_has_more(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);

        for ($i = 0; $i < 5; $i++) {
            $this->createLog(['created_at' => time() + $i]);
        }

        $response = $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log?per_page=3&page=1');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('page', 1)
            ->assertJsonPath('per_page', 3);

        // Page 2
        $response2 = $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log?per_page=3&page=2');

        $response2->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('has_more', false);
    }

    // ── Auth ────────────────────────────────────────────────────────────

    public function test_log_requires_authentication(): void
    {
        $this->getJson('/api/v3/storage/log')
            ->assertUnauthorized();
    }

    public function test_log_requires_read_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/log')
            ->assertForbidden();
    }
}
