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

        $user->tryInsert();

        return [
            'id' => $user->id,
            'token' => $user->token
        ];
    }

    public function bindWechat(int $id, string $appName, string $code, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['id']);

        $wechat = app(WechatService::class);
        $sessionInfo = $wechat->session($code);
        $openid = $sessionInfo['openid'];

        $refreshed = false;
        if ($user->wxCredExisted()) {
            if ($allowRefresh) {
                $refreshed = true;
            }
            else {
                throw new AuthException("Wechat has bound", AuthException::CODE_DUPLICATE_BIND);
            }
        }

        $user->saveWxCredentials($appName, $openid);

        return [
            'refreshed' => $refreshed,
            'token' => $user->token,
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
        $user->tryInsert();

        return [
            'refreshed' => $refreshed,
            'token' => $user->token,
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

        $token = $this->calcToken($user->id);

        return [
            'id' => $user->id,
            'token' => $token
        ];
    }

    public function loginByToken(string $token)
    {
        $decoded = $this->checkToken($token);
        $userId = $decoded->user_id;
        if (!User::find($userId, ['id'])) {
            throw new AuthException("Invalide token [$token]", AuthException::CODE_INVALID_TOKEN);
        }
        return [
            'id' => $userId,
            'token' => $this->calcToken($userId),
        ];
    }

    public function loginAsGuest()
    {
        $user = new User();
        $user->save();
        $token = $this->calcToken($user->id, false);
        return [
            'id' => $user->id,
            'token' => $token
        ];
    }

    public function loginByWechat(string $appName, string $code, $iv = null, $encrypted = null): array
    {
        $wechat = app(WechatService::class);
        $sessionInfo = $wechat->session($code);
        $openid = $sessionInfo['openid'];
        $user = User::byWxOpenid($appName, $openid, ['id']);
        if ($user) {
            $existed = true;
        }
        else {
            $existed = false;
            $user = new User();
            $user->save();
            $user->saveWxCredentials($appName, $openid);
        }

        $token = $this->calcToken($user->id);

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

    private function calcToken(int $userId, bool $willExpire = true): string
    {
        $payload = [
            'user_id' => $userId,
        ];
        if ($willExpire) {
            $payload['exp'] = time() + config('app.jwt.expiry_period');
        }
        $jwtSecret = config('app.jwt.jwt_secret');
        $jwtAlg = config('app.jwt.jwt_alg');

        return JWT::encode($payload, $jwtSecret, $jwtAlg);
    }

}