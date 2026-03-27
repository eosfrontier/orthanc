<?php

namespace App\Services;

use App\Exceptions\TransfersLockedException;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLog;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\DB;

/**
 * Handles item transfers between characters with gate checks,
 * receiver cap enforcement, brokered flag support, and audit logging.
 */
class TransferService
{
    /**
     * Transfer a quantity of an item type from one character to another.
     *
     * @param int         $sourceCharId  The character sending the items.
     * @param int         $targetCharId  The character receiving the items.
     * @param int         $itemTypeId    The item type to transfer.
     * @param int         $quantity      The quantity to transfer (must be positive).
     * @param int         $actorId       The user ID performing the action.
     * @param bool        $brokered      Whether the transfer is brokered by a third party.
     * @param string|null $note          Optional audit note.
     *
     * @return array{source: StorageInventory, target: StorageInventory}
     *
     * @throws TransfersLockedException If transfers are disabled.
     * @throws \DomainException         If source has insufficient quantity or receiver cap would be exceeded.
     */
    public function transfer(
        int $sourceCharId,
        int $targetCharId,
        int $itemTypeId,
        int $quantity,
        int $actorId,
        bool $brokered = false,
        ?string $note = null,
    ): array {
        return DB::transaction(function () use ($sourceCharId, $targetCharId, $itemTypeId, $quantity, $actorId, $brokered, $note) {
            // Gate check
            $enabled = StorageSetting::where('key_name', 'transfers_enabled')->value('value');

            if ($enabled !== '1') {
                throw new TransfersLockedException();
            }

            // Load item type
            $itemType = StorageItemType::findOrFail($itemTypeId);

            // Lock both inventories — lower character_id first to prevent deadlocks
            $firstId = min($sourceCharId, $targetCharId);
            $secondId = max($sourceCharId, $targetCharId);

            $firstInventory = StorageInventory::where('character_id', $firstId)
                ->where('item_type_id', $itemTypeId)
                ->lockForUpdate()
                ->first();

            $secondInventory = StorageInventory::where('character_id', $secondId)
                ->where('item_type_id', $itemTypeId)
                ->lockForUpdate()
                ->first();

            // Assign to source/target based on which is which
            $sourceInventory = ($sourceCharId === $firstId) ? $firstInventory : $secondInventory;
            $targetInventory = ($targetCharId === $firstId) ? $firstInventory : $secondInventory;

            // Source check
            if ($sourceInventory === null) {
                throw new \DomainException(
                    "No inventory found for character {$sourceCharId} and item type {$itemTypeId}."
                );
            }

            if ($sourceInventory->quantity < $quantity) {
                throw new \DomainException(
                    "Insufficient quantity: have {$sourceInventory->quantity}, tried to transfer {$quantity}."
                );
            }

            // Receiver cap check
            $targetQty = $targetInventory ? $targetInventory->quantity : 0;
            $newTargetQty = $targetQty + $quantity;

            if ($itemType->max_quantity !== null && $newTargetQty > $itemType->max_quantity) {
                throw new \DomainException(
                    "Transfer would exceed max quantity of {$itemType->max_quantity} for item type {$itemTypeId}."
                );
            }

            // Deduct source
            $sourceInventory->quantity -= $quantity;
            $sourceInventory->updated_at = time();
            $sourceInventory->save();

            // Credit target — create row if needed
            if ($targetInventory === null) {
                $targetInventory = new StorageInventory([
                    'character_id' => $targetCharId,
                    'item_type_id' => $itemTypeId,
                    'quantity'     => 0,
                ]);
            }

            $targetInventory->quantity = $newTargetQty;
            $targetInventory->updated_at = time();
            $targetInventory->save();

            // Audit log
            StorageLog::create([
                'item_type_id'    => $itemTypeId,
                'quantity'        => $quantity,
                'source_char_id'  => $sourceCharId,
                'target_char_id'  => $targetCharId,
                'actor_id'        => $actorId,
                'action'          => 'transfer',
                'brokered'        => $brokered,
                'note'            => $note,
                'created_at'      => time(),
            ]);

            return ['source' => $sourceInventory, 'target' => $targetInventory];
        });
    }
}
