<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A key-value configuration setting for the storage system.
 *
 * Uses a string primary key (`key_name`) instead of an auto-incrementing
 * integer ID.
 */
class StorageSetting extends Model
{
    protected $table = 'ecc_storage_settings';

    protected $primaryKey = 'key_name';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'key_name',
        'value',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'updated_at' => 'integer',
        'updated_by' => 'integer',
    ];
}
