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

class MiHelper
{

    private $http;
    private $config;
    private $appName;

    function __construct($appName)
    {
        $this->http = new Client();
        $this->appName = $appName;
        $this->config = config("app.migame.{$appName}");
    }

    // https://dev.mi.com/console/doc/detail?pId=1739
    function session(string $session, string $uid)
    {
        $params = [
            'appId' => $this->config['app_id'],
            'session' => $session,
            'uid' => $uid,
        ];
        ksort($params);
        $joined = http_build_query($params);
        $signature = hash_hmac('sha1', $joined, $this->config['app_secret']);

        $params['signature'] = $signature;
        $resp = $this->http->request(
            'POST',
            'https://mis.migc.xiaomi.com/api/biz/service/loginvalidate',
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => $params
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if ($ret['errcode'] !== 200) {
            $errMsg = Arr::get($ret, 'errMsg', 'UNKNOWN');
            throw new AuthException("Error in Migame server login: code[{$ret['errcode']}], msg[{$errMsg}]", AuthException::CODE_AUTH_FAILED);
        }

        return [
            'openid' => $uid,
        ];
    }

}
