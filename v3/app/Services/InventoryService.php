<?php

namespace App\Services;

use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLog;
use Illuminate\Support\Facades\DB;

/**
 * Encapsulates all inventory mutation logic: mint, burn, adjust, and bulk
 * operations with transactional safety, max-quantity cap enforcement, and
 * audit logging.
 */
class InventoryService
{
    /**
     * Mint (add) a quantity of an item type into a character's inventory.
     *
     * @param int         $characterId  The character receiving the items.
     * @param int         $itemTypeId   The item type to mint.
     * @param int         $quantity     The quantity to add (must be positive).
     * @param int         $actorJoomlaId The Joomla user ID performing the action.
     * @param string|null $note         Optional audit note.
     *
     * @return StorageInventory The updated inventory row.
     *
     * @throws \DomainException If the mint would exceed max_quantity.
     */
    public function mint(int $characterId, int $itemTypeId, int $quantity, int $actorJoomlaId, ?string $note = null): StorageInventory
    {
        return DB::transaction(function () use ($characterId, $itemTypeId, $quantity, $actorJoomlaId, $note) {
            $itemType = StorageItemType::findOrFail($itemTypeId);

            $inventory = StorageInventory::where('character_id', $characterId)
                ->where('item_type_id', $itemTypeId)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                $inventory = new StorageInventory([
                    'character_id' => $characterId,
                    'item_type_id' => $itemTypeId,
                    'quantity'     => 0,
                ]);
            }

            $newQty = $inventory->quantity + $quantity;

            if ($itemType->max_quantity !== null && $newQty > $itemType->max_quantity) {
                throw new \DomainException(
                    "Mint would exceed max quantity of {$itemType->max_quantity} for item type {$itemTypeId}."
                );
            }

            $inventory->quantity = $newQty;
            $inventory->updated_at = time();
            $inventory->save();

            StorageLog::create([
                'item_type_id'    => $itemTypeId,
                'quantity'        => $quantity,
                'source_char_id'  => null,
                'target_char_id'  => $characterId,
                'actor_joomla_id' => $actorJoomlaId,
                'action'          => 'mint',
                'note'            => $note,
                'created_at'      => time(),
            ]);

            return $inventory;
        });
    }

    /**
     * Burn (remove) a quantity of an item type from a character's inventory.
     *
     * @param int         $characterId  The character losing the items.
     * @param int         $itemTypeId   The item type to burn.
     * @param int         $quantity     The quantity to remove (must be positive).
     * @param int         $actorJoomlaId The Joomla user ID performing the action.
     * @param string|null $note         Optional audit note.
     *
     * @return StorageInventory The updated inventory row.
     *
     * @throws \DomainException If the character has no inventory or insufficient quantity.
     */
    public function burn(int $characterId, int $itemTypeId, int $quantity, int $actorJoomlaId, ?string $note = null): StorageInventory
    {
        return DB::transaction(function () use ($characterId, $itemTypeId, $quantity, $actorJoomlaId, $note) {
            $inventory = StorageInventory::where('character_id', $characterId)
                ->where('item_type_id', $itemTypeId)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                throw new \DomainException(
                    "No inventory found for character {$characterId} and item type {$itemTypeId}."
                );
            }

            if ($inventory->quantity < $quantity) {
                throw new \DomainException(
                    "Insufficient quantity: have {$inventory->quantity}, tried to burn {$quantity}."
                );
            }

            $inventory->quantity -= $quantity;
            $inventory->updated_at = time();
            $inventory->save();

            StorageLog::create([
                'item_type_id'    => $itemTypeId,
                'quantity'        => $quantity,
                'source_char_id'  => $characterId,
                'target_char_id'  => null,
                'actor_joomla_id' => $actorJoomlaId,
                'action'          => 'burn',
                'note'            => $note,
                'created_at'      => time(),
            ]);

            return $inventory;
        });
    }

    /**
     * Adjust an existing inventory row by a positive or negative delta.
     *
     * @param int         $inventoryId   The inventory row ID to adjust.
     * @param int         $delta         The signed quantity change (non-zero).
     * @param int         $actorJoomlaId The Joomla user ID performing the action.
     * @param string|null $note          Optional audit note.
     *
     * @return StorageInventory The updated inventory row.
     *
     * @throws \DomainException If delta is zero, inventory not found, cap exceeded, or insufficient quantity.
     */
    public function adjust(int $inventoryId, int $delta, int $actorJoomlaId, ?string $note = null): StorageInventory
    {
        if ($delta === 0) {
            throw new \DomainException('Delta must not be zero.');
        }

        return DB::transaction(function () use ($inventoryId, $delta, $actorJoomlaId, $note) {
            $inventory = StorageInventory::where('id', $inventoryId)
                ->lockForUpdate()
                ->first();

            if ($inventory === null) {
                throw new \DomainException("Inventory row {$inventoryId} not found.");
            }

            $newQty = $inventory->quantity + $delta;

            if ($delta > 0) {
                $itemType = StorageItemType::findOrFail($inventory->item_type_id);
                if ($itemType->max_quantity !== null && $newQty > $itemType->max_quantity) {
                    throw new \DomainException(
                        "Adjust would exceed max quantity of {$itemType->max_quantity}."
                    );
                }
            }

            if ($delta < 0 && $newQty < 0) {
                throw new \DomainException(
                    "Insufficient quantity: have {$inventory->quantity}, tried to remove " . abs($delta) . '.'
                );
            }

            $inventory->quantity = $newQty;
            $inventory->updated_at = time();
            $inventory->save();

            $action = $delta > 0 ? 'mint' : 'burn';
            $characterId = $inventory->character_id;

            StorageLog::create([
                'item_type_id'    => $inventory->item_type_id,
                'quantity'        => abs($delta),
                'source_char_id'  => $action === 'burn' ? $characterId : null,
                'target_char_id'  => $action === 'mint' ? $characterId : null,
                'actor_joomla_id' => $actorJoomlaId,
                'action'          => $action,
                'note'            => $note,
                'created_at'      => time(),
            ]);

            return $inventory;
        });
    }

    /**
     * Mint items to multiple characters in bulk. Each mint runs in its own
     * transaction; failures are collected rather than aborting the batch.
     *
     * @param int    $itemTypeId    The item type to mint.
     * @param int    $quantity      The quantity to mint per character.
     * @param int[]  $characterIds  Array of character IDs to receive items.
     * @param int    $actorJoomlaId The Joomla user ID performing the action.
     * @param string|null $note     Optional audit note.
     *
     * @return array{succeeded: array<int, array{character_id: int, new_quantity: int}>, failed: array<int, array{character_id: int, error: string}>}
     */
    public function bulk(int $itemTypeId, int $quantity, array $characterIds, int $actorJoomlaId, ?string $note = null): array
    {
        $succeeded = [];
        $failed = [];

        foreach ($characterIds as $characterId) {
            try {
                $inventory = $this->mint($characterId, $itemTypeId, $quantity, $actorJoomlaId, $note);
                $succeeded[] = [
                    'character_id' => $characterId,
                    'new_quantity' => $inventory->quantity,
                ];
            } catch (\Throwable $e) {
                $failed[] = [
                    'character_id' => $characterId,
                    'error'        => $e->getMessage(),
                ];
            }
        }

        return ['succeeded' => $succeeded, 'failed' => $failed];
    }
}
