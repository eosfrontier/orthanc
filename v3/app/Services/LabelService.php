<?php

namespace App\Services;

use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLabelToken;
use App\Models\StorageLabelTokenItem;
use App\Models\StorageLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Handles creation and claiming of physical label tokens.
 *
 * Each token can carry one or more item lines (item type + quantity).
 *
 * Tokens can be created from two sources:
 * - "mint" — a fresh token with no inventory impact until claimed.
 * - "burn" — items are removed from a character's inventory and encoded into the token.
 *
 * When a token is claimed, all item lines are minted into the claiming character's inventory.
 */
class LabelService
{
    /**
     * Create a new label token with one or more item lines.
     *
     * For burn-source tokens, the specified quantities are deducted from the
     * source character's inventory inside a transaction with `label_burn` log entries.
     * For mint-source tokens, no inventory change occurs until the token is claimed.
     *
     * @param array $data    Token data: items (array of {item_type_id, quantity}), source, source_char_id, note, expires_at.
     * @param int   $actorId The user ID performing the action.
     *
     * @return StorageLabelToken The created token record with items eager-loaded.
     *
     * @throws \DomainException If items array is empty, any quantity is not positive,
     *                          source is 'burn' and source_char_id is missing, or inventory is insufficient.
     */
    public function create(array $data, int $actorId): StorageLabelToken
    {
        $source = $data['source'] ?? 'mint';
        $items = $data['items'] ?? [];

        if (empty($items)) {
            throw new \DomainException('At least one item is required.');
        }

        foreach ($items as $item) {
            $qty = $item['quantity'] ?? 1;
            if ($qty <= 0) {
                throw new \DomainException('Token quantity must be a positive integer.');
            }
        }

        if ($source === 'burn') {
            return $this->createBurnToken($data, $actorId, $items);
        }

        $token = StorageLabelToken::create([
            'token'          => Str::uuid()->toString(),
            'note'           => $data['note'] ?? null,
            'source'         => 'mint',
            'source_char_id' => null,
            'created_by'     => $actorId,
            'created_at'     => time(),
            'expires_at'     => $data['expires_at'] ?? null,
        ]);

        foreach ($items as $item) {
            StorageLabelTokenItem::create([
                'label_token_id' => $token->id,
                'item_type_id'   => $item['item_type_id'],
                'quantity'       => $item['quantity'] ?? 1,
            ]);
        }

        return $token->load('items');
    }

    /**
     * Look up a token by its UUID string, eager-loading items and their item types.
     *
     * @param string $token The UUID token string.
     *
     * @return StorageLabelToken|null The token, or null if not found.
     */
    public function getByToken(string $token): ?StorageLabelToken
    {
        return StorageLabelToken::with('items.itemType')
            ->where('token', $token)
            ->first();
    }

    /**
     * Return all unclaimed tokens, ordered newest-first, with items eager-loaded.
     *
     * @return Collection<int, StorageLabelToken>
     */
    public function getUnclaimed(): Collection
    {
        return StorageLabelToken::unclaimed()
            ->with('items.itemType')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Claim a token, minting all item lines into the character's inventory.
     *
     * Inside a transaction: re-fetches the token with a row lock, validates it
     * is unclaimed and not expired, increments (or creates) the character's
     * inventory rows for each item line, writes `label_claim` log entries,
     * and marks the token claimed.
     *
     * @param string $token   The UUID token string.
     * @param int    $charId  The character claiming the token.
     * @param int    $actorId The user ID performing the action.
     *
     * @return StorageLabelToken The claimed token with items loaded.
     *
     * @throws \DomainException If the token does not exist, is already claimed, is expired,
     *                          or claiming would exceed an item type's max_quantity cap.
     */
    public function claim(string $token, int $charId, int $actorId): StorageLabelToken
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

            $tokenItems = StorageLabelTokenItem::where('label_token_id', $lockedToken->id)->get();

            foreach ($tokenItems as $tokenItem) {
                $itemType = StorageItemType::findOrFail($tokenItem->item_type_id);

                $inventory = StorageInventory::where('character_id', $charId)
                    ->where('item_type_id', $tokenItem->item_type_id)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $inventory ? $inventory->quantity : 0;
                $newQty = $currentQty + $tokenItem->quantity;

                if ($itemType->max_quantity !== null && $newQty > $itemType->max_quantity) {
                    throw new \DomainException(
                        "Claiming would exceed max quantity of {$itemType->max_quantity} for item type {$tokenItem->item_type_id}."
                    );
                }

                if ($inventory === null) {
                    $inventory = new StorageInventory([
                        'character_id' => $charId,
                        'item_type_id' => $tokenItem->item_type_id,
                        'quantity'     => 0,
                    ]);
                }

                $inventory->quantity = $newQty;
                $inventory->updated_at = time();
                $inventory->save();

                StorageLog::create([
                    'item_type_id'   => $tokenItem->item_type_id,
                    'quantity'       => $tokenItem->quantity,
                    'source_char_id' => null,
                    'target_char_id' => $charId,
                    'actor_id'       => $actorId,
                    'action'         => 'label_claim',
                    'brokered'       => false,
                    'note'           => $lockedToken->note,
                    'created_at'     => time(),
                ]);
            }

            $lockedToken->claimed_by = $charId;
            $lockedToken->claimed_at = time();
            $lockedToken->save();

            return $lockedToken->load('items');
        });
    }

    /**
     * Create a burn-source token, deducting items from the source character's inventory.
     *
     * @param array $data    Token data including source_char_id and item details.
     * @param int   $actorId The user ID performing the action.
     * @param array $items   Validated item lines [{item_type_id, quantity}, ...].
     *
     * @return StorageLabelToken The created token record with items eager-loaded.
     *
     * @throws \DomainException If source_char_id is missing or inventory is insufficient.
     */
    private function createBurnToken(array $data, int $actorId, array $items): StorageLabelToken
    {
        if (empty($data['source_char_id'])) {
            throw new \DomainException('source_char_id is required when source is burn.');
        }

        return DB::transaction(function () use ($data, $actorId, $items) {
            $token = StorageLabelToken::create([
                'token'          => Str::uuid()->toString(),
                'note'           => $data['note'] ?? null,
                'source'         => 'burn',
                'source_char_id' => $data['source_char_id'],
                'created_by'     => $actorId,
                'created_at'     => time(),
                'expires_at'     => $data['expires_at'] ?? null,
            ]);

            foreach ($items as $item) {
                $quantity = $item['quantity'] ?? 1;

                $inventory = StorageInventory::where('character_id', $data['source_char_id'])
                    ->where('item_type_id', $item['item_type_id'])
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
                    'item_type_id'   => $item['item_type_id'],
                    'quantity'       => $quantity,
                    'source_char_id' => $data['source_char_id'],
                    'target_char_id' => null,
                    'actor_id'       => $actorId,
                    'action'         => 'label_burn',
                    'brokered'       => false,
                    'note'           => $data['note'] ?? null,
                    'created_at'     => time(),
                ]);

                StorageLabelTokenItem::create([
                    'label_token_id' => $token->id,
                    'item_type_id'   => $item['item_type_id'],
                    'quantity'       => $quantity,
                ]);
            }

            return $token->load('items');
        });
    }
}
