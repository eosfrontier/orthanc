<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A top-level grouping for storage item types.
 *
 * Categories organise item types into logical groups (e.g. "Weapons",
 * "Resources") and can be soft-deleted via the `deleted_at` epoch column.
 */
class StorageCategory extends Model
{
    protected $table = 'ecc_storage_categories';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'created_at',
        'created_by',
        'deleted_at',
        'is_system',
    ];

    protected $casts = [
        'created_at' => 'integer',
        'created_by' => 'integer',
        'deleted_at' => 'integer',
        'is_system'  => 'boolean',
    ];

    /**
     * Scope to only non-deleted categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * The item types that belong to this category.
     */
    public function itemTypes(): HasMany
    {
        return $this->hasMany(StorageItemType::class, 'category_id');
    }
}
