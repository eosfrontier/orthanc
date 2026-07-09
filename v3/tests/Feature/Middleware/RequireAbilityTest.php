<?php

namespace Tests\Feature\Middleware;

use App\Enums\TokenAbility;
use App\Models\ApiConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequireAbilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'require-ability:storage:read'])
            ->get('/test/single-ability', fn () => response()->json(['ok' => true]));

        Route::middleware(['auth:sanctum', 'require-ability:storage:write,storage:admin'])
            ->get('/test/multi-ability', fn () => response()->json(['ok' => true]));
    }

    public function test_unauthenticated_returns_401(): void
    {
        $this->getJson('/test/single-ability')
            ->assertStatus(401);
    }

    public function test_token_lacking_ability_returns_403(): void
    {
        $consumer = ApiConsumer::create([
            'name' => 'test-consumer',
            'created_at' => time(),
        ]);

        $token = $consumer->createToken('test', [TokenAbility::Write->value]);

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/test/single-ability')
            ->assertStatus(403);
    }

    public function test_token_with_required_ability_returns_200(): void
    {
        $consumer = ApiConsumer::create([
            'name' => 'test-consumer',
            'created_at' => time(),
        ]);

        $token = $consumer->createToken('test', [TokenAbility::Read->value]);

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/test/single-ability')
            ->assertStatus(200);
    }

    public function test_token_with_any_one_of_multiple_abilities_returns_200(): void
    {
        $consumer = ApiConsumer::create([
            'name' => 'test-consumer',
            'created_at' => time(),
        ]);

        $token = $consumer->createToken('test', [TokenAbility::Admin->value]);

        $this->withHeader('Authorization', 'Bearer ' . $token->plainTextToken)
            ->getJson('/test/multi-ability')
            ->assertStatus(200);
    }
}
