<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018/11/23
 * Time: 9:15 AM
 */

return [

    'ttgame' => [
        'pan_tt' => [
            'app_id' => env('PAN_TTGAME_APP_ID'),
            'secret' => env('PAN_TTGAME_APP_SECRET'),
        ],
        'card_tt' => [
            'app_id' => env('CARD_TTGAME_APP_ID'),
            'secret' => env('CARD_TTGAME_APP_SECRET'),
        ],
        'pipo_tt' => [
            'app_id' => env('PIPO_TTGAME_APP_ID'),
            'secret' => env('PIPO_TTGAME_APP_SECRET'),
        ],
        'sea_tt' => [
            'app_id' => env('SEA_TTGAME_APP_ID'),
            'secret' => env('SEA_TTGAME_APP_SECRET'),
        ],
    ],

    // EasyWechat configuration
    'wechat' => [
        'pan' => [
            'app_id' => env('PAN_WECHAT_APP_ID'),
            'secret' => env('PAN_WECHAT_APP_SECRET'),
            'response_type' => 'array',
            'log' => [
                'level' => 'DEBUG',
                'file' => storage_path('logs/wechat.log'),
            ],
        ],
        'card' => [
            'app_id' => env('CARD_WECHAT_APP_ID'),
            'secret' => env('CARD_WECHAT_APP_SECRET'),
            'response_type' => 'array',
            'log' => [
                'level' => 'DEBUG',
                'file' => storage_path('logs/wechat.log'),
            ],
        ],
        'pipo' => [
            'app_id' => env('PIPO_WECHAT_APP_ID'),
            'secret' => env('PIPO_WECHAT_APP_SECRET'),
            'response_type' => 'array',
            'log' => [
                'level' => 'DEBUG',
                'file' => storage_path('logs/wechat.log'),
            ],
        ],
        'sea' => [
            'app_id' => env('SEA_WECHAT_APP_ID'),
            'secret' => env('SEA_WECHAT_APP_SECRET'),
            'response_type' => 'array',
            'log' => [
                'level' => 'DEBUG',
                'file' => storage_path('logs/wechat.log'),
            ],
        ],
    ],

    'jwt' => [
        'jwt_secret' => env('JWT_SECRET'),
        'expiry_period' => env('LOGIN_TOKEN_TTL'),
        'jwt_alg' => 'HS256',
    ],

    'db' => [
        'user_table' => env('USER_TABLE')
    ],

];