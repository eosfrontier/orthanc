<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

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
