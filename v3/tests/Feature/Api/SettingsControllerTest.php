<?php

namespace Tests\Feature\Api;

use App\Models\ApiConsumer;
use App\Models\StorageSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for SettingsController covering list, update,
 * unknown key rejection, and auth/ability enforcement.
 */
class SettingsControllerTest extends TestCase
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
     * Seed the default settings.
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

    // ── Index ───────────────────────────────────────────────────────────

    public function test_index_returns_all_settings(): void
    {
        $auth = $this->createConsumerWithToken(['storage:read']);
        $this->seedSettings();

        $this->withToken($auth['token'])
            ->getJson('/api/v3/storage/settings')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v3/storage/settings')
            ->assertUnauthorized();
    }

    // ── Update ──────────────────────────────────────────────────────────

    public function test_update_changes_setting_value(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $this->seedSettings();

        $this->withToken($auth['token'])
            ->putJson('/api/v3/storage/settings', [
                'key'   => 'transfers_enabled',
                'value' => '0',
            ])
            ->assertOk()
            ->assertJsonPath('data.value', '0');

        $this->assertDatabaseHas('ecc_storage_settings', [
            'key_name' => 'transfers_enabled',
            'value'    => '0',
        ]);
    }

    public function test_update_broker_fee_sonuren(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);
        $this->seedSettings();

        $this->withToken($auth['token'])
            ->putJson('/api/v3/storage/settings', [
                'key'   => 'broker_fee_sonuren',
                'value' => '50',
            ])
            ->assertOk()
            ->assertJsonPath('data.value', '50');
    }

    public function test_update_rejects_unknown_key(): void
    {
        $auth = $this->createConsumerWithToken(['storage:admin']);

        $this->withToken($auth['token'])
            ->putJson('/api/v3/storage/settings', [
                'key'   => 'nonexistent_setting',
                'value' => '1',
            ])
            ->assertUnprocessable();
    }

    public function test_update_requires_admin_ability(): void
    {
        $auth = $this->createConsumerWithToken(['storage:write']);

        $this->withToken($auth['token'])
            ->putJson('/api/v3/storage/settings', [
                'key'   => 'transfers_enabled',
                'value' => '0',
            ])
            ->assertForbidden();
    }
}
