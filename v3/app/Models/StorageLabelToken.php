<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single-use token printed on a physical label that can be claimed
 * by a character to receive items.
 */
class StorageLabelToken extends Model
{
    protected $table = 'ecc_storage_label_tokens';

    public $timestamps = false;

    protected $fillable = [
        'token',
        'item_type_id',
        'quantity',
        'note',
        'source',
        'source_char_id',
        'created_by',
        'created_at',
        'claimed_by',
        'claimed_at',
        'expires_at',
    ];

    protected $casts = [
        'item_type_id'   => 'integer',
        'quantity'       => 'integer',
        'source_char_id' => 'integer',
        'created_by'     => 'integer',
        'created_at'     => 'integer',
        'claimed_by'     => 'integer',
        'claimed_at'     => 'integer',
        'expires_at'     => 'integer',
    ];

    /**
     * Scope to only tokens that have not yet been claimed.
     */
    public function scopeUnclaimed(Builder $query): Builder
    {
        return $query->whereNull('claimed_by');
    }

    /**
     * Scope to only tokens that are unclaimed and not expired.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('claimed_by')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', time());
            });
    }

    /**
     * The item type this label token grants.
     */
    public function itemType(): BelongsTo
    {
        return $this->belongsTo(StorageItemType::class, 'item_type_id');
    }
}
