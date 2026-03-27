<?php

namespace App\Services;

use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLabelToken;
use App\Models\StorageLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Handles creation and claiming of physical label tokens.
 *
 * Tokens can be created from two sources:
 * - "mint" — a fresh token with no inventory impact until claimed.
 * - "burn" — items are removed from a character's inventory and encoded into the token.
 *
 * When a token is claimed, items are minted into the claiming character's inventory.
 */
class LabelService
{
    /**
     * Create a new label token.
     *
     * For burn-source tokens, the specified quantity is deducted from the
     * source character's inventory inside a transaction with a `label_burn` log entry.
     * For mint-source tokens, no inventory change occurs until the token is claimed.
     *
     * @param array $data    Token data: item_type_id, quantity, source, source_char_id, note, expires_at.
     * @param int   $actorId The user ID performing the action.
     *
     * @return StorageLabelToken The created token record.
     *
     * @throws \DomainException If quantity is not positive, source is 'burn' and source_char_id is missing, or inventory is insufficient.
     */
    public function create(array $data, int $actorId): StorageLabelToken
    {
        $source = $data['source'] ?? 'mint';
        $quantity = $data['quantity'] ?? 1;

        if ($quantity <= 0) {
            throw new \DomainException('Token quantity must be a positive integer.');
        }

        if ($source === 'burn') {
            return $this->createBurnToken($data, $actorId, $quantity);
        }

        return StorageLabelToken::create([
            'token'          => Str::uuid()->toString(),
            'item_type_id'   => $data['item_type_id'],
            'quantity'       => $quantity,
            'note'           => $data['note'] ?? null,
            'source'         => 'mint',
            'source_char_id' => null,
            'created_by'     => $actorId,
            'created_at'     => time(),
            'expires_at'     => $data['expires_at'] ?? null,
        ]);
    }

    /**
     * Look up a token by its UUID string, eager-loading the item type.
     *
     * @param string $token The UUID token string.
     *
     * @return StorageLabelToken|null The token, or null if not found.
     */
    public function getByToken(string $token): ?StorageLabelToken
    {
        return StorageLabelToken::with('itemType')
            ->where('token', $token)
            ->first();
    }

    /**
     * Return all unclaimed tokens, ordered newest-first, with item types eager-loaded.
     *
     * @return Collection<int, StorageLabelToken>
     */
    public function getUnclaimed(): Collection
    {
        return StorageLabelToken::unclaimed()
            ->with('itemType')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Claim a token, minting items into the character's inventory.
     *
     * Inside a transaction: re-fetches the token with a row lock, validates it
     * is unclaimed and not expired, increments (or creates) the character's
     * inventory row, writes a `label_claim` log entry, and marks the token claimed.
     *
     * @param string $token   The UUID token string.
     * @param int    $charId  The character claiming the token.
     * @param int    $actorId The user ID performing the action.
     *
     * @return StorageInventory The updated inventory row.
     *
     * @throws \DomainException If the token does not exist, is already claimed, is expired,
     *                          or claiming would exceed the item type's max_quantity cap.
     */
    public function claim(string $token, int $charId, int $actorId): StorageInventory
    {
        $labelToken = $this->getByToken($token);

        if ($labelToken === null) {
            throw new \DomainException("Label token not found: {$token}");
        }

        if ($labelToken->claimed_by !== null) {
            throw new \DomainException('Label token has already been claimed.');
        }

        if ($labelToken->expires_at !== null && $labelToken->expires_at <= time()) {
            throw new \DomainException('Label token has expired.');
        }

        return DB::transaction(function () use ($labelToken, $charId, $actorId) {
            // Re-fetch with lock for race protection
            $lockedToken = StorageLabelToken::where('id', $labelToken->id)
                ->lockForUpdate()
                ->first();

            if ($lockedToken->claimed_by !== null) {
                throw new \DomainException('Label token has already been claimed.');
            }

            if ($lockedToken->expires_at !== null && $lockedToken->expires_at <= time()) {
                throw new \DomainException('Label token has expired.');
            }

            $itemType = StorageItemType::findOrFail($lockedToken->item_type_id);

            // Find or create inventory row
            $inventory = StorageInventory::where('character_id', $charId)
                ->where('item_type_id', $lockedToken->item_type_id)
                ->lockForUpdate()
                ->first();

            $currentQty = $inventory ? $inventory->quantity : 0;
            $newQty = $currentQty + $lockedToken->quantity;

            // Check max_quantity cap
            if ($itemType->max_quantity !== null && $newQty > $itemType->max_quantity) {
                throw new \DomainException(
                    "Claiming would exceed max quantity of {$itemType->max_quantity} for item type {$lockedToken->item_type_id}."
                );
            }

            if ($inventory === null) {
                $inventory = new StorageInventory([
                    'character_id' => $charId,
                    'item_type_id' => $lockedToken->item_type_id,
                    'quantity'     => 0,
                ]);
            }

            $inventory->quantity = $newQty;
            $inventory->updated_at = time();
            $inventory->save();

            // Audit log
            StorageLog::create([
                'item_type_id'   => $lockedToken->item_type_id,
                'quantity'       => $lockedToken->quantity,
                'source_char_id' => null,
                'target_char_id' => $charId,
                'actor_id'       => $actorId,
                'action'         => 'label_claim',
                'brokered'       => false,
                'note'           => $lockedToken->note,
                'created_at'     => time(),
            ]);

            // Mark token as claimed
            $lockedToken->claimed_by = $charId;
            $lockedToken->claimed_at = time();
            $lockedToken->save();

            return $inventory;
        });
    }

    /**
     * Create a burn-source token, deducting items from the source character's inventory.
     *
     * @param array $data     Token data including source_char_id and item details.
     * @param int   $actorId  The user ID performing the action.
     * @param int   $quantity The validated positive quantity.
     *
     * @return StorageLabelToken The created token record.
     *
     * @throws \DomainException If source_char_id is missing or inventory is insufficient.
     */
    private function createBurnToken(array $data, int $actorId, int $quantity): StorageLabelToken
    {
        if (empty($data['source_char_id'])) {
            throw new \DomainException('source_char_id is required when source is burn.');
        }

        return DB::transaction(function () use ($data, $actorId, $quantity) {
            $inventory = StorageInventory::where('character_id', $data['source_char_id'])
                ->where('item_type_id', $data['item_type_id'])
                ->lockForUpdate()
                ->first();

            $currentQty = $inventory ? $inventory->quantity : 0;

            if ($currentQty < $quantity) {
                throw new \DomainException(
                    "Insufficient quantity: have {$currentQty}, tried to burn {$quantity}."
                );
            }

            $inventory->quantity -= $quantity;
            $inventory->updated_at = time();
            $inventory->save();

            StorageLog::create([
                'item_type_id'   => $data['item_type_id'],
                'quantity'       => $quantity,
                'source_char_id' => $data['source_char_id'],
                'target_char_id' => null,
                'actor_id'       => $actorId,
                'action'         => 'label_burn',
                'brokered'       => false,
                'note'           => $data['note'] ?? null,
                'created_at'     => time(),
            ]);

            return StorageLabelToken::create([
                'token'          => Str::uuid()->toString(),
                'item_type_id'   => $data['item_type_id'],
                'quantity'       => $quantity,
                'note'           => $data['note'] ?? null,
                'source'         => 'burn',
                'source_char_id' => $data['source_char_id'],
                'created_by'     => $actorId,
                'created_at'     => time(),
                'expires_at'     => $data['expires_at'] ?? null,
            ]);
        });
    }
}
