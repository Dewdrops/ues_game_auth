<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018-12-26
 * Time: 10:11
 */

namespace App\Services;


use App\Exceptions\AuthException;
use App\Exceptions\GamePayException;
use App\User;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BilibiliHelper
{

    private $client;
    private $config;
    private $appName;

    function __construct($appName)
    {
        $this->client = new Client();
        $this->appName = $appName;
        $this->config = config("app.blgame.{$appName}");
    }

    function session(string $code)
    {
        $resp = $this->client->request(
            'GET',
            'https://miniapp.bilibili.com/api/sns/jscode2session',
            [
                'query' => [
                    'appid' => $this->config['app_id'],
                    'secret' => $this->config['secret'],
                    'js_code' => $code,
                    'grant_type' => 'authorization_code',
                ]
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if (Arr::has($ret, 'errcode') && $ret['errcode'] !== 0) {
            throw new AuthException(
                "Error in Blgame code2session call: [{$ret['errmsg']}]",
                AuthException::CODE_AUTH_FAILED
            );
        }
        return $ret;
    }

}
