<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single-use token printed on a physical label that can be claimed
 * by a character to receive one or more item types.
 */
class StorageLabelToken extends Model
{
    protected $table = 'ecc_storage_label_tokens';

    public $timestamps = false;

    protected $fillable = [
        'token',
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
     * The item lines on this label token.
     */
    public function items(): HasMany
    {
        return $this->hasMany(StorageLabelTokenItem::class, 'label_token_id');
    }
}
