<?php

namespace Tests\Feature\Console\Commands;

use App\Models\ApiConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_issue_token_creates_consumer_and_token(): void
    {
        $this->artisan('orthanc:issue-token', [
            'name' => 'test-consumer',
            '--abilities' => 'storage:read',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('api_consumers', ['name' => 'test-consumer']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_issue_token_reuses_existing_consumer(): void
    {
        ApiConsumer::create(['name' => 'test-consumer', 'created_at' => time()]);

        $this->artisan('orthanc:issue-token', [
            'name' => 'test-consumer',
            '--abilities' => 'storage:read',
        ])->assertExitCode(0);

        $this->assertDatabaseCount('api_consumers', 1);
    }

    public function test_issue_token_last_line_is_plain_token(): void
    {
        $this->artisan('orthanc:issue-token', [
            'name' => 'test-consumer',
            '--abilities' => 'storage:read',
        ])->expectsOutputToContain('|')
          ->assertExitCode(0);
    }

    public function test_issue_token_fails_with_invalid_ability(): void
    {
        $this->artisan('orthanc:issue-token', [
            'name' => 'test-consumer',
            '--abilities' => 'storage:read,invalid:perm',
        ])->assertFailed();
    }

    public function test_issue_token_fails_without_abilities(): void
    {
        $this->artisan('orthanc:issue-token', [
            'name' => 'test-consumer',
        ])->assertFailed();
    }

    public function test_revoke_token_deletes_token(): void
    {
        $consumer = ApiConsumer::create(['name' => 'test-consumer', 'created_at' => time()]);
        $token = $consumer->createToken('test', ['storage:read']);

        $this->artisan('orthanc:revoke-token', [
            'name' => 'test-consumer',
            'token_id' => $token->accessToken->id,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_revoke_token_fails_for_nonexistent_consumer(): void
    {
        $this->artisan('orthanc:revoke-token', [
            'name' => 'ghost',
            'token_id' => 999,
        ])->assertFailed();
    }

    public function test_revoke_token_fails_for_wrong_consumer(): void
    {
        $owner = ApiConsumer::create(['name' => 'owner', 'created_at' => time()]);
        ApiConsumer::create(['name' => 'other', 'created_at' => time()]);
        $token = $owner->createToken('test', ['storage:read']);

        $this->artisan('orthanc:revoke-token', [
            'name' => 'other',
            'token_id' => $token->accessToken->id,
        ])->assertFailed();

        // Token should still exist
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
