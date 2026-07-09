<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * An external system that consumes the storage API.
 *
 * Each consumer is issued Sanctum tokens with specific TokenAbility
 * scopes that control which endpoints it may access.
 */
class ApiConsumer extends Model
{
    use HasApiTokens;

    protected $table = 'api_consumers';

    public $timestamps = false;

    protected $fillable = ['name', 'created_at'];

    protected $casts = [
        'created_at' => 'integer',
    ];
}
