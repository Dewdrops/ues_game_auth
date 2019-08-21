<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018-12-26
 * Time: 10:11
 */

namespace App\Services;


use App\Exceptions\AuthException;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class TtHelper
{

    private $client;
    private $config;

    function __construct($appName)
    {
        $this->client = new Client();
        $this->config = config("app.ttgame.{$appName}");
    }

    function session(string $code)
    {
        $resp = $this->client->request(
            'GET',
            'https://developer.toutiao.com/api/apps/jscode2session',
            [
                'query' => [
                    'appid' => $this->config['app_id'],
                    'secret' => $this->config['secret'],
                    'code' => $code,
                ]
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if (Arr::has($ret, 'errcode')) {
            throw new AuthException(
                "Error in Ttgame code2session call: [{$ret['errmsg']}]",
                AuthException::CODE_AUTH_FAILED
            );
        }
        return $ret;
    }

}