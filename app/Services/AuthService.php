<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018-11-23
 * Time: 16:19
 */

namespace App\Services;


use App\Exceptions\AuthException;
use App\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;

class AuthService
{

    private $jwt_secret;
    private $jwt_alg;

    public function __construct()
    {
        $this->jwt_secret = config('app.jwt.jwt_secret');
        $this->jwt_alg = config('app.jwt.jwt_alg');
    }

    public function checkToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, $this->jwt_secret, [$this->jwt_alg]);
            return $decoded;
        }
        catch (ExpiredException $exception) {
            throw new AuthException("Login session expired", AuthException::CODE_LOGIN_SESSION_EXPIRED);
        }
    }

    public function loginByWechat(string $code, $iv = null, $encrypted = null): array
    {
        $wechat = app(WechatService::class);
        $sessionInfo = $wechat->session($code);
        $sessionKey = $sessionInfo['session_key'];
        $openid = $sessionInfo['openid'];
        $user = User::where(['wx_openid' => $openid])
            ->select(['id'])
            ->first();
        if ($user) {
            $existed = true;
            $user->wx_session_key = $sessionKey;
        }
        else {
            $existed = false;
            $user = new User();
            $user->wx_session_key = $sessionKey;
            $user->wx_openid = $openid;
//            if ($iv !== null) {
//                $decrypted = $wechat->decryptUserData($sessionKey, $iv, $encrypted);
//                $user->wx_unionid = $decrypted['unionid'];
//            }
        }

        $expired = time() + config('app.jwt.expiry_period');
        $payload = [
            'user_id' => $user->id,
            'exp' => $expired
        ];
        $token = JWT::encode($payload, $this->jwt_secret, $this->jwt_alg);
        $user->token = $token;
        $user->save();

        $ret = [
            'id' => $user->id,
            'token' => $token,
            'existed' => $existed,
        ];
        if (isset($decrypted)) {
            $ret['decrypted'] = $decrypted;
        }

        return $ret;
    }

}