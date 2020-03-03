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
use Illuminate\Support\Str;

class OppoHelper
{

    private $http;
    private $config;
    private $appName;

    function __construct($appName)
    {
        $this->http = new Client();
        $this->appName = $appName;
        $this->config = config("app.oppogame.{$appName}");
    }

    // https://open.oppomobile.com/wiki/doc#id=10522
    function session(string $code)
    {
        $timestamp = (string) (int) (microtime(true) * 1000);
        $params = [
            'token' => $code,
            'appKey' => $this->config['app_key'],
            'appSecret' => $this->config['app_secret'],
            'pkgName' => $this->config['pkg_name'],
            'timeStamp' => $timestamp,
        ];
        ksort($params);
        $joined = http_build_query($params);
        $signature = Str::upper(hash('md5', $joined));

        $resp = $this->http->request(
            'GET',
            'https://play.open.oppomobile.com/instant-game-open/userInfo',
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'pkgName' => $this->config['pkg_name'],
                    'token' => $code,
                    'timeStamp' => $timestamp,
                    'sign' => $signature,
                    'version' => '1.0.0',
                ]
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if ($ret['errCode'] !== 200) {
            throw new AuthException("Error in Oppogame server login: code[{$ret['errCode']}], msg[{$ret['errMsg']}]", AuthException::CODE_AUTH_FAILED);
        }

        return $ret['userInfo'];
    }

    public function updateRank($userId, $score)
    {
        $timestamp = (string) (int) (microtime(true) * 1000);
        $params = [
            'userId' => $userId,
            'rankType' => '0',
            'rankScore' => (string) $score,
            'appKey' => $this->config['app_key'],
            'appSecret' => $this->config['app_secret'],
            'pkgName' => $this->config['pkg_name'],
            'timeStamp' => $timestamp,
        ];
        ksort($params);
        $joined = http_build_query($params);
        $signature = Str::upper(hash('md5', $joined));

        $resp = $this->http->request(
            'POST',
            'https://play.open.oppomobile.com/instant-game-open/rank/update',
            [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'userId' => $userId,
                    'pkgName' => $this->config['pkg_name'],
                    'rankType' => '0',
                    'rankScore' => (string) $score,
                    'timeStamp' => $timestamp,
                    'sign' => $signature,
                ]
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if ($ret['errCode'] !== "200") {
            throw new AuthException("Error in Oppogame update rank: userId[$userId], score[$score], code[{$ret['errCode']}], msg[{$ret['errMsg']}]", AuthException::CODE_AUTH_FAILED);
        }
    }

}
