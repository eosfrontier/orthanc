<?php

namespace App\Console\Commands;

use App\Enums\TokenAbility;
use App\Models\ApiConsumer;
use Illuminate\Console\Command;

/**
 * Artisan command to issue a Sanctum API token for an ApiConsumer.
 *
 * Creates the consumer if it does not already exist, then generates
 * a token with the specified ability scopes. The plain-text token
 * is printed as the last line of output for easy scripting.
 */
class IssueToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orthanc:issue-token {name} {--abilities=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Issue a Sanctum API token for an API consumer';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $abilitiesOption = $this->option('abilities');

        if (empty($abilitiesOption)) {
            $this->error('The --abilities option is required and must not be empty.');
            return self::FAILURE;
        }

        $abilities = array_map('trim', explode(',', $abilitiesOption));
        $validAbilities = TokenAbility::values();
        $invalid = array_diff($abilities, $validAbilities);

        if (! empty($invalid)) {
            $this->error('Invalid abilities: ' . implode(', ', $invalid));
            $this->error('Valid abilities: ' . implode(', ', $validAbilities));
            return self::FAILURE;
        }

        $consumer = ApiConsumer::firstOrCreate(
            ['name' => $name],
            ['created_at' => time()],
        );

        $token = $consumer->createToken($name, $abilities);

        $this->info("Consumer: {$consumer->name}");
        $this->info('Abilities: ' . implode(', ', $abilities));
        $this->info("Token ID: {$token->accessToken->id}");
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
