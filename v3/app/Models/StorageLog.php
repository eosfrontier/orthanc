<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An audit-trail entry recording a storage transaction.
 *
 * Every transfer, grant, or removal of items generates a log row that
 * captures the involved characters, quantity, and action type.
 */
class StorageLog extends Model
{
    protected $table = 'ecc_storage_log';

    public $timestamps = false;

    protected $fillable = [
        'item_type_id',
        'quantity',
        'source_char_id',
        'target_char_id',
        'actor_joomla_id',
        'action',
        'brokered',
        'note',
        'created_at',
    ];

    protected $casts = [
        'item_type_id'    => 'integer',
        'quantity'        => 'integer',
        'source_char_id'  => 'integer',
        'target_char_id'  => 'integer',
        'actor_joomla_id' => 'integer',
        'brokered'        => 'boolean',
        'created_at'      => 'integer',
    ];

    /**
     * The item type involved in this log entry.
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(StorageItemType::class, 'item_type_id');
    }
}
