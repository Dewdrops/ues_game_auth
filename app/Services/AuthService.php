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
use Illuminate\Support\Facades\Hash;

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
            throw new AuthException("Login session expired", AuthException::CODE_SESSION_EXPIRED);
        }
    }

    public function register(string $username, string $password): array
    {
        $user = new User();
        $user->password = Hash::make($password);
        $user->username = $username;

        try {
            $user->save();
        }
        catch (\Exception $e) {
            if ($e->getCode() === "23505") {
                throw new AuthException(
                    "Duplicate username [$username]",
                    AuthException::CODE_DUPLICATE_USERNAME
                );
            }
            throw $e;
        }

        return [
            'id' => $user->id,
            'token' => $this->generateToken([
                'user_id' => $user->id,
            ])
        ];
    }

    public function bindWechat(int $id, string $code, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['wx_openid']);

        $refreshed = false;
        if ($user->wx_openid) {
            if ($allowRefresh) {
                $refreshed = true;
            }
            else {
                throw new AuthException("Wechat has bound", AuthException::CODE_DUPLICATE_BIND);
            }
        }

        $wechat = app(WechatService::class);
        $sessionInfo = $wechat->session($code);
        $user->wx_session_key = $sessionInfo['session_key'];
        $user->wx_openid = $sessionInfo['openid'];
        $user->save();

        return [
            'refreshed' => $refreshed
        ];
    }

    public function loginByPassword(string $username, string $password): array
    {
        $user = User::where([
            'username' => $username,
        ])
            ->select(['id', 'password'])
            ->first();

        if (!$user) {
            throw new AuthException("User[$username] not existed", AuthException::CODE_PASSWORD_WRONG);
        }

        if (!Hash::check($password, $user->password)) {
            throw new AuthException("Password wrong for user[$username]", AuthException::CODE_PASSWORD_WRONG);
        }

        return [
            'id' => $user->id,
            'token' => $this->generateToken([
                'user_id' => $user->id,
            ])
        ];
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
        $user->save();

        $token = $this->generateToken([
            'user_id' => $user->id,
        ]);
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

    private function generateToken(array $payload): string
    {
        $expired = time() + config('app.jwt.expiry_period');
        $payload['exp'] = $expired;
        return JWT::encode($payload, $this->jwt_secret, $this->jwt_alg);
    }

}