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

    public function checkToken(string $token)
    {
        try {
            $jwtSecret = config('app.jwt.jwt_secret');
            $jwtAlg = config('app.jwt.jwt_alg');
            $decoded = JWT::decode($token, $jwtSecret, [$jwtAlg]);
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

        $user->tryInsert(true);

        return [
            'id' => $user->id,
            'token' => $user->token
        ];
    }

    public function bindWechat(int $id, string $code, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['id', 'wx_openid']);

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

        $user->tryInsert(false);

        return [
            'refreshed' => $refreshed
        ];
    }

    public function bindPassword(int $id, string $username, string $password, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['id', 'username']);

        $refreshed = false;
        if ($user->username) {
            if ($allowRefresh) {
                $refreshed = true;
            }
            else {
                throw new AuthException("Password has bound for user id[$id]", AuthException::CODE_DUPLICATE_BIND);
            }
        }

        $user->username = $username;
        $user->password = Hash::make($password);
        $user->tryInsert(false);

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

        $token = $user->saveToken();

        return [
            'id' => $user->id,
            'token' => $token
        ];
    }

    public function loginAsGuest(?int $userId)
    {
        if ($userId === null) {
            $user = new User();
            $user->save();
            $userId = $user->id;
        }
        else {
            $user = User::findOrFail($userId);
        }

        $token = $user->saveToken();

        return [
            'id' => $userId,
            'token' => $token
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
        }
        $user->save();

        $token = $user->saveToken();

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