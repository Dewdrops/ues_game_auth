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
use Illuminate\Support\Str;

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
            'token' => $this->calcToken(['user_id' => $user->id]),
        ];
    }

    private function getPlatformDriver(string $appName)
    {
        if (Str::endsWith(Str::lower($appName), '_tt')) {
            return new TtHelper($appName);
        }
        else {
            return new WechatHelper($appName);
        }
    }

    public function bindWechat(int $id, string $appName, string $code, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['id']);

        $driver = $this->getPlatformDriver($appName);
        $sessionInfo = $driver->session($code);
        $openid = $sessionInfo['openid'];

        $refreshed = false;
        if ($user->wxCredExisted($appName)) {
            if ($allowRefresh) {
                $refreshed = true;
            }
            else {
                throw new AuthException("App[$appName] has bound for user[$id]", AuthException::CODE_DUPLICATE_BIND);
            }
        }

        $user->saveWxCredentials($appName, $openid);

        return [
            'refreshed' => $refreshed,
            'token' => $this->calcToken([
                'user_id' => $user->id,
                'app' => $appName
            ]),
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
            'token' => $this->calcToken(['user_id' => $user->id]),
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

        $token = $this->calcToken(['user_id' => $user->id]);

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
            'token' => $this->calcToken(['user_id' => $userId]),
        ];
    }

    public function loginForDebug(int $userId)
    {
        if (!$this->checkDebugUserId($userId) || !User::find($userId, ['id'])) {
            throw new AuthException("Invalid user id [$userId]", AuthException::CODE_AUTH_FAILED);
        }
        return [
            'id' => $userId,
            'token' => $this->calcToken(['user_id' => $userId]),
        ];
    }

    public function loginAsGuest()
    {
        $user = new User();
        $user->save();
        $token = $this->calcToken($user->id, false);
        return [
            'id' => ['user_id' => $user->id],
            'token' => $token
        ];
    }

    public function loginByTtgame(string $appName, string $code): array
    {
        return $this->loginByWechat($appName, $code);
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

        $token = $this->calcToken([
            'user_id' => $user->id,
            'app' => $appName,
        ]);

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
        $driver = $this->getPlatformDriver($appName);
        $sessionInfo = $driver->session($code);
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

        $token = $this->calcToken(['user_id' => $user->id]);

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

    private function calcToken(array $payload, bool $willExpire = true): string
    {
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
        $validDebugIds = explode(',', config('app.debug.valid_user_ids'));
        return in_array($id, $validDebugIds);
    }

}
