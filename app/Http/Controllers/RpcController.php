<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2019-02-02
 * Time: 10:58
 */

namespace App\Http\Controllers;


use App\Services\AuthService;
use App\Services\GamePayService;
use App\Services\RankService;
use App\Support\RpcParams;
use App\Traits\UesRpcDispatcher;
use Laravel\Lumen\Routing\Controller;

class RpcController extends Controller
{

    use UesRpcDispatcher;

    public function loginByWechat(AuthService $service, RpcParams $params)
    {
        return $service->loginByWechat(
            $params['app_name'],
            $params['code'],
            $params->get('userInfo')
        );
    }

    public function loginByFacebook(AuthService $service, RpcParams $params)
    {
        return $service->loginByFacebook(
            $params['app_name'],
            $params['signature'],
            $params->get('userInfo')
        );
    }

    public function loginByTtgame(AuthService $service, RpcParams $params)
    {
        return $service->loginByTtgame(
            $params['app_name'],
            $params['code'],
            $params->get('userInfo')
        );
    }

    public function loginByOppo(AuthService $service, RpcParams $params)
    {
        return $service->loginByOppo(
            $params['app_name'],
            $params['code']
        );
    }

    public function loginByVivo(AuthService $service, RpcParams $params)
    {
        return $service->loginByVivo(
            $params['app_name'],
            $params['code']
        );
    }

    public function loginByPassword(AuthService $service, RpcParams $params)
    {
        return $service->loginByPassword(
            $params['app_name'],
            $params['username'],
            $params['password']
        );
    }

    public function loginForDebug(AuthService $service, RpcParams $params)
    {
        return $service->loginForDebug($params['app_name'], $params['user_id']);
    }

    public function loginByToken(AuthService $service, RpcParams $params)
    {
        return $service->loginByToken($params['app_name'], $params['token']);
    }

    public function loginAsGuest(AuthService $service, RpcParams $params)
    {
        return $service->loginAsGuest($params['app_name']);
    }

    public function register(AuthService $service, RpcParams $params)
    {
        return $service->register(
            $params['app_name'],
            $params['username'],
            $params['password'],
            $params->get('email'),
            $params->get('data')
        );
    }

    public function sendResetPasswordEmail(AuthService $service, RpcParams $params)
    {
        $service->sendResetPasswordEmail(
            $params['email'],
            $params['callbackUrl']
        );
    }

    public function resetPassword(AuthService $service, RpcParams $params)
    {
        $service->resetPassword($params['newPassword'], $params['token']);
    }

    public function getOpenid(AuthService $service, RpcParams $params)
    {
        return $service->getOpenid($params['id'], $params['app_name']);
    }

    public function bindWechat(AuthService $service, RpcParams $params)
    {
        return $service->bindWechat(
            $params['id'], $params['app_name'], $params['code'], $params->get("allowRefresh", false)
        );
    }

    public function bindPassword(AuthService $service, RpcParams $params)
    {
        return $service->bindPassword(
            $params['id'], $params['app_name'], $params['username'], $params['password'], $params->get("allowRefresh", false)
        );
    }

    public function updateRank(RankService $service, RpcParams $params)
    {
        $service->updateRank($params['app_name'], $params['id'], $params['score']);
    }

    public function getBalance(GamePayService $service, RpcParams $params)
    {
        return $service->getBalance($params['id'], $params['app_name']);
    }

    public function gamePay(GamePayService $service, RpcParams $params)
    {
        return $service->gamePay($params['id'], $params['app_name'], $params['amount'], $params['bill_no']);
    }

    public function checkToken(AuthService $service, RpcParams $params)
    {
        return $service->checkToken($params['token'], $params->get('with_data', false));
    }

}
