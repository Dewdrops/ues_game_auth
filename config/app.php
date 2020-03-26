<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018/11/23
 * Time: 9:15 AM
 */

return [

    'migame' => [
        'card_mi' => [
            'app_id' => env('CARD_MI_APP_ID'),
            'app_secret' => env('CARD_MI_APP_SECRET'),
        ],
    ],

    'oppogame' => [
        'card_oppo' => [
            'app_key' => env('CARD_OPPOGAME_APP_KEY'),
            'app_secret' => env('CARD_OPPOGAME_APP_SECRET'),
            'pkg_name' => env('CARD_OPPOGAME_PKG_NAME'),
        ],
        'sea_oppo' => [
            'app_key' => env('SEA_OPPOGAME_APP_KEY'),
            'app_secret' => env('SEA_OPPOGAME_APP_SECRET'),
            'pkg_name' => env('SEA_OPPOGAME_PKG_NAME'),
        ],
        'pan_oppo' => [
            'app_key' => env('PAN_OPPOGAME_APP_KEY'),
            'app_secret' => env('PAN_OPPOGAME_APP_SECRET'),
            'pkg_name' => env('PAN_OPPOGAME_PKG_NAME'),
        ],
        'pipo_oppo' => [
            'app_key' => env('PIPO_OPPOGAME_APP_KEY'),
            'app_secret' => env('PIPO_OPPOGAME_APP_SECRET'),
            'pkg_name' => env('PIPO_OPPOGAME_PKG_NAME'),
        ],
        'fly_oppo' => [
            'app_key' => env('FLY_OPPOGAME_APP_KEY'),
            'app_secret' => env('FLY_OPPOGAME_APP_SECRET'),
            'pkg_name' => env('FLY_OPPOGAME_PKG_NAME'),
        ],
        'cocokun_oppo' => [
            'app_key' => env('COCOKUN_OPPOGAME_APP_KEY'),
            'app_secret' => env('COCOKUN_OPPOGAME_APP_SECRET'),
            'pkg_name' => env('COCOKUN_OPPOGAME_PKG_NAME'),
        ],
    ],

    'vivogame' => [
        'pan_vivo' => [
            'app_key' => env('PAN_VIVOGAME_APP_KEY'),
            'app_secret' => env('PAN_VIVOGAME_APP_SECRET'),
            'pkg_name' => env('PAN_VIVOGAME_PKG_NAME'),
        ],
        'pipo_vivo' => [
            'app_key' => env('PIPO_VIVOGAME_APP_KEY'),
            'app_secret' => env('PIPO_VIVOGAME_APP_SECRET'),
            'pkg_name' => env('PIPO_VIVOGAME_PKG_NAME'),
        ],
        'card_vivo' => [
            'app_key' => env('CARD_VIVOGAME_APP_KEY'),
            'app_secret' => env('CARD_VIVOGAME_APP_SECRET'),
            'pkg_name' => env('CARD_VIVOGAME_PKG_NAME'),
        ],
        'sea_vivo' => [
            'app_key' => env('SEA_VIVOGAME_APP_KEY'),
            'app_secret' => env('SEA_VIVOGAME_APP_SECRET'),
            'pkg_name' => env('SEA_VIVOGAME_PKG_NAME'),
        ],
    ],

    'facebook' => [
        'pan_fb' => [
            'app_id' => env('PAN_FACEBOOK_APP_ID'),
            'secret' => env('PAN_FACEBOOK_APP_SECRET'),
        ],
    ],

    'ttgame' => [
        'pan_tt' => [
            'app_id' => env('PAN_TTGAME_APP_ID'),
            'secret' => env('PAN_TTGAME_APP_SECRET'),
            'pay_secret' => env('PAN_TTGAME_PAY_SECRET'),
        ],
        'card_tt' => [
            'app_id' => env('CARD_TTGAME_APP_ID'),
            'secret' => env('CARD_TTGAME_APP_SECRET'),
            'pay_secret' => env('CARD_TTGAME_PAY_SECRET'),
        ],
        'pipo_tt' => [
            'app_id' => env('PIPO_TTGAME_APP_ID'),
            'secret' => env('PIPO_TTGAME_APP_SECRET'),
        ],
        'sea_tt' => [
            'app_id' => env('SEA_TTGAME_APP_ID'),
            'secret' => env('SEA_TTGAME_APP_SECRET'),
        ],
        'mahjong_tt' => [
            'app_id' => env('MAHJONG_TTGAME_APP_ID'),
            'secret' => env('MAHJONG_TTGAME_APP_SECRET'),
        ],
        'cocokun_tt' => [
            'app_id' => env('COCOKUN_TTGAME_APP_ID'),
            'secret' => env('COCOKUN_TTGAME_APP_SECRET'),
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
        'mahjong' => [
            'app_id' => env('MAHJONG_WECHAT_APP_ID'),
            'secret' => env('MAHJONG_WECHAT_APP_SECRET'),
            'response_type' => 'array',
            'log' => [
                'level' => 'DEBUG',
                'file' => storage_path('logs/wechat.log'),
            ],
        ],
        'cocokun_wx' => [
            'app_id' => env('COCOKUN_WECHAT_APP_ID'),
            'secret' => env('COCOKUN_WECHAT_APP_SECRET'),
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

    'debug' => [
        'sql_slow_threshold' => env('SQL_SLOW_THRESHOLD'),
        'valid_user_ids' => env('VALID_DEBUG_USER_IDS'),
    ],

    'rpc' => [
        'endpoint' => [
            'notification' => env('JSONRPC_ENDPOINT_NOTIFICATION'),
        ]
    ],

    'token' => [
        'email_link_ttl_hours' => env('EMAIL_LINK_TTL_HOURS')
    ],

];
