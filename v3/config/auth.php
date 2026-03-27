<?php

return [

    'defaults' => [
        'guard' => 'sanctum',
    ],

    'guards' => [
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'api_consumers',
        ],
    ],

    'providers' => [
        'api_consumers' => [
            'driver' => 'eloquent',
            'model' => App\Models\ApiConsumer::class,
        ],
    ],

];
