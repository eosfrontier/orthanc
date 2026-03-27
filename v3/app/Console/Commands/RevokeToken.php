<?php

namespace App\Console\Commands;

use App\Models\ApiConsumer;
use Illuminate\Console\Command;

/**
 * Artisan command to revoke a Sanctum API token for an ApiConsumer.
 *
 * Looks up the consumer by name and the token by ID, ensuring the token
 * belongs to that consumer before deleting it.
 */
class RevokeToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orthanc:revoke-token {name} {token_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke a Sanctum API token for an API consumer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $tokenId = $this->argument('token_id');

        $consumer = ApiConsumer::where('name', $name)->first();

        if (! $consumer) {
            $this->error("Consumer '{$name}' not found.");
            return self::FAILURE;
        }

        $token = $consumer->tokens()->where('id', $tokenId)->first();

        if (! $token) {
            $this->error("Token #{$tokenId} not found for consumer '{$name}'.");
            return self::FAILURE;
        }

        $token->delete();

        $this->info("Token #{$tokenId} for consumer '{$name}' has been revoked.");

        return self::SUCCESS;
    }
}
