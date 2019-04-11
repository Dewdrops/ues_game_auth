<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018/11/23
 * Time: 9:15 AM
 */

return [

    // EasyWechat configuration
    'wechat' => [
        'app_id' => env('WECHAT_APP_ID'),
        'secret' => env('WECHAT_APP_SECRET'),
        'response_type' => 'array',
        'log' => [
            'level' => env('WECHAT_LOG_LEVEL', 'DEBUG'),
            'file' => storage_path('logs/wechat.log'),
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