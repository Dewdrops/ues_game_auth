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

    private function generateNonce(int $digit = 32)
    {
        $nonce = '';
        $nonce .= rand(1, 9);
        for ($i = 1; $i < $digit; $i++) {
            $nonce .= rand(0, 9);
        }
        return $nonce;
    }

    // http://minigame.vivo.com.cn/documents/#/api/service/newaccount?id=签名生成步骤
    function session(string $code)
    {
        $timestamp = (string) (int) (microtime(true) * 1000);
        $nonce = $this->generateNonce();
        $params = [
            'token' => $code,
            'appKey' => $this->config['app_key'],
            'appSecret' => $this->config['app_secret'],
            'pkgName' => $this->config['pkg_name'],
            'timestamp' => $timestamp,
            'nonce' => $nonce,
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
                    'nonce' => $nonce,
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
