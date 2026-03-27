<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A type of item that can be stored in character inventories.
 *
 * Item types define the properties shared by all instances of a particular
 * item, such as whether it is stackable and its maximum stack quantity.
 */
class StorageItemType extends Model
{
    protected $table = 'ecc_storage_item_types';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'category_id',
        'stackable',
        'max_quantity',
        'is_system',
        'created_at',
        'created_by',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'category_id'  => 'integer',
        'stackable'    => 'boolean',
        'max_quantity'  => 'integer',
        'is_system'    => 'boolean',
        'created_at'   => 'integer',
        'created_by'   => 'integer',
        'updated_at'   => 'integer',
        'deleted_at'   => 'integer',
    ];

    /**
     * Scope to only non-deleted item types.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * The category this item type belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(StorageCategory::class, 'category_id');
    }
}
