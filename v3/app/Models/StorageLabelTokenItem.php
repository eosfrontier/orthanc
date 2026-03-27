<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single item line on a label token, linking an item type and quantity.
 */
class StorageLabelTokenItem extends Model
{
    protected $table = 'ecc_storage_label_token_items';

    public $timestamps = false;

    protected $fillable = [
        'label_token_id',
        'item_type_id',
        'quantity',
    ];

    protected $casts = [
        'label_token_id' => 'integer',
        'item_type_id'   => 'integer',
        'quantity'        => 'integer',
    ];

    /**
     * The label token this item belongs to.
     */
    public function labelToken(): BelongsTo
    {
        return $this->belongsTo(StorageLabelToken::class, 'label_token_id');
    }

    /**
     * The item type for this line.
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(StorageItemType::class, 'item_type_id');
    }
}
