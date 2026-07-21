<?php return [
    /*
    |--------------------------------------------------------------------------
    | Minecraft Stuff
    |--------------------------------------------------------------------------
    |
    | Additional params used by our custom features.
    |
    */

    'rcon' => [
        'server' => env('MC_RCON_SERVER', ''),
        'port' => env('MC_RCON_PORT', ''),
        'password' => env('MC_RCON_PASSWORD', ''),
    ],
];
