<?php

namespace App\Services;

use App\Enums\LogAction;
use App\Exceptions\TransfersLockedException;
use App\Models\StorageInventory;
use App\Models\StorageItemType;
use App\Models\StorageLog;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\DB;

/**
 * Handles item transfers between characters with gate checks,
 * receiver cap enforcement, broker fee deduction, and audit logging.
 */
class TransferService
{
    /** @var string Sonuren item name used to look up the system currency. */
    private const SONUREN_ITEM_NAME = 'Sonuren';

    /**
     * Transfer a quantity of an item type from one character to another.
     *
     * When brokered, a broker fee in Sonuren is deducted from the sender
     * and a separate `broker_fee` log row is created in the same transaction.
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
     * @throws \DomainException         If quantity is not positive, source and target are the same,
     *                                  source has insufficient quantity, receiver cap would be exceeded,
     *                                  or sender lacks Sonuren for broker fee.
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
        if ($quantity <= 0) {
            throw new \DomainException('Transfer quantity must be a positive integer.');
        }

        if ($sourceCharId === $targetCharId) {
            throw new \DomainException('Source and target character must be different.');
        }

        return DB::transaction(function () use ($sourceCharId, $targetCharId, $itemTypeId, $quantity, $actorId, $brokered, $note) {
            // Gate check
            $enabled = StorageSetting::where('key_name', 'transfers_enabled')->value('value');

            if ($enabled !== '1') {
                throw new TransfersLockedException();
            }

            // Broker fee deduction (locks the sender's Sonuren row).
            // This lock targets a different item_type row than the transfer itself
            // (unless transferring Sonuren), so it cannot deadlock with the
            // per-item locks below which use min/max character_id ordering.
            if ($brokered) {
                $this->deductBrokerFee($sourceCharId, $actorId);
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
                'action'          => LogAction::Transfer->value,
                'brokered'        => $brokered,
                'note'            => $note,
                'created_at'      => time(),
            ]);

            return ['source' => $sourceInventory, 'target' => $targetInventory];
        });
    }

    /**
     * Deduct the broker fee in Sonuren from the sender's inventory.
     *
     * Locks the sender's Sonuren row, validates sufficient balance,
     * deducts the fee, and creates a `broker_fee` audit log entry.
     *
     * @param int $sourceCharId The character paying the fee.
     * @param int $actorId      The user ID performing the action.
     *
     * @throws \DomainException If the sender has insufficient Sonuren for the fee.
     */
    private function deductBrokerFee(int $sourceCharId, int $actorId): void
    {
        $fee = (int) StorageSetting::where('key_name', 'broker_fee_sonuren')->value('value');

        if ($fee <= 0) {
            throw new \RuntimeException('Broker fee setting is missing or invalid.');
        }

        $sonurenType = StorageItemType::where('name', self::SONUREN_ITEM_NAME)
            ->where('is_system', true)
            ->first();

        if ($sonurenType === null) {
            throw new \RuntimeException('Sonuren item type not found.');
        }

        $sonurenInventory = StorageInventory::where('character_id', $sourceCharId)
            ->where('item_type_id', $sonurenType->id)
            ->lockForUpdate()
            ->first();

        if ($sonurenInventory === null) {
            throw new \DomainException('No Sonuren inventory for character ' . $sourceCharId . '.');
        }

        if ($sonurenInventory->quantity < $fee) {
            throw new \DomainException(
                "Insufficient Sonuren for broker fee: have {$sonurenInventory->quantity}, need {$fee}."
            );
        }

        $sonurenInventory->quantity -= $fee;
        $sonurenInventory->updated_at = time();
        $sonurenInventory->save();

        StorageLog::create([
            'item_type_id'    => $sonurenType->id,
            'quantity'        => $fee,
            'source_char_id'  => $sourceCharId,
            'target_char_id'  => null,
            'actor_id'        => $actorId,
            'action'          => LogAction::BrokerFee->value,
            'brokered'        => true,
            'note'            => null,
            'created_at'      => time(),
        ]);
    }
}
