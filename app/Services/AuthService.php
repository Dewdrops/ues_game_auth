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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AuthService
{

    const VALID_DEBUG_USER_IDS = [1, 2, 17, 18, 21, 999999];

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
            'token' => $this->calcToken($user->id),
        ];
    }

    public function bindWechat(int $id, string $appName, string $code, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['id']);

        $wechat = new WechatHelper($appName);
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
            'token' => $this->calcToken($user->id),
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
            'token' => $this->calcToken($user->id),
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

    public function loginForDebug(int $userId)
    {
        if (!$this->checkDebugUserId($userId) || !User::find($userId, ['id'])) {
            throw new AuthException("Invalid user id [$userId]", AuthException::CODE_AUTH_FAILED);
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

    public function loginByTtgame(string $appName, string $code): array
    {
        $ttHelper = new TtHelper($appName);
        $sessionInfo = $ttHelper->session($code);
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

        return $ret;
    }

    public function loginByFacebook(string $appName, string $signature): array
    {
        $exploded = explode('.', $signature);
        $sig = base64_decode(str_replace(array('-', '_'), array('+', '/'), $exploded[0]));
        $hash = hash_hmac('sha256', $exploded[1], config("app.facebook.{$appName}.secret"), true);
        if ($hash !== $sig) {
            throw new AuthException('Facebook signature invalid', AuthException::CODE_AUTH_FAILED);
        }

        $data = json_decode(base64_decode($exploded[1]), true);

        $openid = $data['player_id'];
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

    public function loginByWechat(string $appName, string $code, $iv = null, $encrypted = null): array
    {
        $wechat = new WechatHelper($appName);
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

    public function getOpenid(int $userId, string $appName): ?string
    {
        $user = User::findOrFail($userId, ['id']);

        return $user->getOpenid($appName);
    }

    private function checkDebugUserId(int $id): bool
    {
        return Arr::has(self::VALID_DEBUG_USER_IDS, $id);
    }

}
