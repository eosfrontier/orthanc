<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A row in a character's inventory, linking a character to an item type
 * with a specific quantity.
 */
class StorageInventory extends Model
{
    protected $table = 'ecc_storage_inventory';

    public $timestamps = false;

    protected $fillable = [
        'character_id',
        'item_type_id',
        'quantity',
        'updated_at',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'item_type_id' => 'integer',
        'quantity'     => 'integer',
        'updated_at'   => 'integer',
    ];

    /**
     * The item type for this inventory row.
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(StorageItemType::class, 'item_type_id');
    }
}
