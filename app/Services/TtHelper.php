<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018-12-26
 * Time: 10:11
 */

namespace App\Services;


use App\Exceptions\AuthException;
use App\User;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TtHelper
{

    private $client;
    private $config;
    private $appName;

    const PAY_RECORD_TABLE = 'game_pay';

    function __construct($appName)
    {
        $this->client = new Client();
        $this->appName = $appName;
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

        if (Arr::has($ret, 'errcode') && $ret['errcode'] !== 0) {
            throw new AuthException(
                "Error in Ttgame code2session call: [{$ret['errmsg']}]",
                AuthException::CODE_AUTH_FAILED
            );
        }
        return $ret;
    }

    // https://developer.toutiao.com/docs/server/auth/accessToken.html
    private function getAccessToken(): string
    {
        $resp = $this->client->request(
            'GET',
            'https://developer.toutiao.com/api/apps/token',
            [
                'query' => [
                    'appid' => $this->config['app_id'],
                    'secret' => $this->config['secret'],
                    'grant_type' => "client_credential",
                ]
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        if (Arr::has($ret, 'errcode') && $ret['errcode'] !== 0) {
            throw new \Exception("Error in Ttgame get accesss token call: [{$ret['errmsg']}]" );
        }
        return $ret['access_token'];
    }

    // https://developer.toutiao.com/docs/game/payment/genSignature.html
    private function generateSig($params, $httpMethod, $requestUri)
    {
        ksort($params);
        $stringA = http_build_query($params);
        $stringB = $stringA . "&org_loc={$requestUri}&method={$httpMethod}";

        return hash_hmac( "sha256", $stringB, config('app.ttgame.pay_secret'));
    }

    //https://developer.toutiao.com/docs/game/payment/pay.html
    public function gamePay(User $user, int $amount, ?string $billNo)
    {
        if ($billNo === null) {
            $billNo = Str::uuid();
        }

        $params = [
            'access_token' => $this->getAccessToken(),
            'appid' => $this->config['app_id'],
            'openid' => $user->getOpenid($this->appName),
            'pf' => "android",
            'ts' => time(),
            'zone_id' => "1",
            'amt' => $amount,
            'bill_no' => $billNo,
        ];
        $params['mp_sig'] = $this->generateSig(
            $params,
            'POST',
            '/api/apps/game/wallet/game_pay'
        );

        DB::table(self::PAY_RECORD_TABLE)
            ->insert([
                'user_id' => $user->id,
                'app_name' => $this->appName,
                'type' => 'GAME_PAY',
                'amount' => $amount,
                'bill_no' => $billNo,
                'processed' => false,
            ]);

        $resp = $this->client->request(
            'POST',
            'https://developer.toutiao.com/api/apps/game/wallet/game_pay',
            [
                'json' => $params
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        Log::info('Call toutiao game_pay', ['ret' => $ret]);

        if (Arr::has($ret, 'errcode') && $ret['errcode'] !== 0) {
            throw new \Exception("Error in Ttgame get_balance call: [{$ret['errmsg']}]" );
        }

        DB::table(self::PAY_RECORD_TABLE)
            ->where('bill_no', '=', $billNo)
            ->update([
                'processed' => true
            ]);

        return [
            'bill_no' => $ret['bill_no'],
            'balance' => $ret['balance'],
        ];
    }

    // https://developer.toutiao.com/docs/game/payment/getBalance.html#请求参数
    public function getBalance(User $user)
    {
        $params = [
            'access_token' => $this->getAccessToken(),
            'appid' => $this->config['app_id'],
            'openid' => $user->getOpenid($this->appName),
            'pf' => "android",
            'ts' => time(),
            'zone_id' => "1",
        ];
        $params['mp_sig'] = $this->generateSig(
            $params,
            'POST',
            '/api/apps/game/wallet/get_balance'
        );

        $resp = $this->client->request(
            'POST',
            'https://developer.toutiao.com/api/apps/game/wallet/get_balance',
            [
                'json' => $params
            ]
        );
        $ret = json_decode($resp->getBody()->getContents(), true);

        Log::info('Call toutiao get_balance', ['ret' => $ret]);

        if (Arr::has($ret, 'errcode') && $ret['errcode'] !== 0) {
            throw new \Exception("Error in Ttgame get_balance call: [{$ret['errmsg']}]" );
        }

        $balanceTt = $ret['balance'];
        $payRemaining = DB::table(self::PAY_RECORD_TABLE)
            ->where([
                'user_id' => $user->id,
                'app_name' => $this->appName,
                'type' => 'GAME_PAY',
                'processed' => true,
            ])
            ->sum('amount');

        return [
            'balance' => $balanceTt - $payRemaining,
        ];
    }

}