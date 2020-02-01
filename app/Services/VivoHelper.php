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

class VivoHelper
{

    private $http;
    private $config;
    private $appName;

    function __construct($appName)
    {
        $this->http = new Client();
        $this->appName = $appName;
        $this->config = config("app.vivogame.{$appName}");
    }

    // http://minigame.vivo.com.cn/documents/#/api/service/newaccount?id=签名生成步骤
    function session(string $code)
    {
        $timestamp = (int)(microtime(true) * 1000);
        $nounce = md5(uniqid(microtime(true), true));
        $params = [
            'token' => $code,
            'appKey' => $this->config['app_id'],
            'appSecret' => $this->config['secret'],
            'pkgName' => $this->config['pkg_name'],
            'timestamp' => $timestamp,
            'nonce' => $nounce,
        ];
        ksort($params);
        $joined = http_build_query($params);
        $signature = hash('sha256', $joined);

        $resp = $this->http->request(
            'GET',
            'https://quickgame.vivo.com.cn/api/quickgame/cp/account/userInfo',
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'pkgName' => $this->config['pkg_name'],
                    'timestamp' => $timestamp,
                    'nonce' => $nounce,
                    'token' => $code,
                    'signature' => $signature
                ]
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if ($ret['code'] !== 0) {
            throw new AuthException("Error in Vivogame server login: code[{$ret['code']}], msg[{$ret['msg']}]", AuthException::CODE_AUTH_FAILED);
        }

        return [
            'openid' => $ret['data']['openId'],
        ];
    }

}
