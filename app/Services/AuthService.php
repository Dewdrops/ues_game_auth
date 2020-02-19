<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018-11-23
 * Time: 16:19
 */

namespace App\Services;


use App\Exceptions\AuthException;
use App\Support\UesRpcClient;
use App\Token;
use App\User;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{

    public function checkToken(string $token, bool $withData = false)
    {
        try {
            $jwtSecret = config('app.jwt.jwt_secret');
            $jwtAlg = config('app.jwt.jwt_alg');
            $decoded = JWT::decode($token, $jwtSecret, [$jwtAlg]);

            if ($withData) {
                $user = User::findOrFail($decoded->user_id, ['username']);
                $decoded->_data = [
                    'username' => $user->username
                ];
            }

            return $decoded;
        }
        catch (ExpiredException $exception) {
            throw new AuthException("Login session expired", AuthException::CODE_SESSION_EXPIRED);
        }
    }

    public function register(string $app, string $username, string $password, ?string $email, ?array $data): array
    {
        $user = new User();
        $user->password = Hash::make($password);
        $user->username = $username;
        if (!is_null($email)) {
            $user->email = $email;
        }
        if (!is_null($data)) {
            $user->data = $data;
        }

        $user->tryInsert();

        return [
            'id' => $user->id,
            'token' => $this->generateToken([
                'user_id' => $user->id,
                'app' => $app
            ]),
        ];
    }

    private function getPlatformDriver(string $app)
    {
        if (Str::endsWith(Str::lower($app), '_tt')) {
            return new TtHelper($app);
        }
        else if (Str::endsWith(Str::lower($app), '_vivo')) {
            return new VivoHelper($app);
        }
        else {
            return new WechatHelper($app);
        }
    }

    public function bindWechat(int $id, string $app, string $code, bool $allowRefresh)
    {
        $user = User::findOrFail($id, ['id']);

        $driver = $this->getPlatformDriver($app);
        $sessionInfo = $driver->session($code);
        $openid = $sessionInfo['openid'];

        $refreshed = false;
        if ($user->wxCredExisted($app)) {
            if ($allowRefresh) {
                $refreshed = true;
            }
            else {
                throw new AuthException("App[$app] has bound for user[$id]", AuthException::CODE_DUPLICATE_BIND);
            }
        }

        $user->saveWxCredentials($app, $openid);

        return [
            'refreshed' => $refreshed,
            'token' => $this->generateToken([
                'user_id' => $user->id,
                'app' => $app
            ]),
        ];
    }

    public function bindPassword(int $id, string $app, string $username, string $password, bool $allowRefresh)
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
            'token' => $this->generateToken([
                'user_id' => $user->id,
                'app' => $app
            ]),
        ];
    }

    public function loginByPassword(string $app, string $username, string $password): array
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
                'app' => $app
            ])
        ];
    }

    public function loginByToken(string $app, string $token)
    {
        $decoded = $this->checkToken($token);
        $userId = $decoded->user_id;
        if (!User::find($userId, ['id'])) {
            throw new AuthException("Invalide token [$token]", AuthException::CODE_INVALID_TOKEN);
        }
        return [
            'id' => $userId,
            'token' => $this->generateToken([
                'user_id' => $userId,
                'app' => $app
            ]),
        ];
    }

    public function loginForDebug(string $app, int $userId)
    {
        if (!$this->checkDebugUserId($userId) || !User::find($userId, ['id'])) {
            throw new AuthException("Invalid user id [$userId]", AuthException::CODE_AUTH_FAILED);
        }
        return [
            'id' => $userId,
            'token' => $this->generateToken([
                'user_id' => $userId,
                'app' => $app
            ]),
        ];
    }

    public function loginAsGuest(string $app)
    {
        $user = new User();
        $user->save();
        $token = $this->generateToken(['user_id' => $user->id, 'app' => $app], false);
        return [
            'id' => $user->id,
            'token' => $token
        ];
    }

    public function loginByTtgame(string $app, string $code): array
    {
        return $this->loginByWechat($app, $code);
    }

    public function loginByVivo(string $app, string $code): array
    {
        $driver = new VivoHelper($app);
        $sessionInfo = $driver->session($code);
        $openid = $sessionInfo['openid'];
        $user = User::byWxOpenid($app, $openid, ['id']);
        if ($user) {
            $existed = true;
        }
        else {
            $existed = false;
            $user = new User();
            $user->save();
            $user->saveWxCredentials($app, $openid);
        }

        $token = $this->generateToken(['user_id' => $user->id, 'app' => $app]);

        return [
            'id' => $user->id,
            'token' => $token,
            'nickName' => $sessionInfo['nickName'],
            'smallAvatar' => $sessionInfo['smallAvatar'],
            'biggerAvatar' => $sessionInfo['biggerAvatar'],
            'existed' => $existed,
        ];
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

        $token = $this->generateToken([
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

    public function loginByWechat(string $app, string $code, $iv = null, $encrypted = null): array
    {
        $driver = $this->getPlatformDriver($app);
        $sessionInfo = $driver->session($code);
        $openid = $sessionInfo['openid'];
        $user = User::byWxOpenid($app, $openid, ['id']);
        if ($user) {
            $existed = true;
        }
        else {
            $existed = false;
            $user = new User();
            $user->save();
            $user->saveWxCredentials($app, $openid);
        }

        $token = $this->generateToken(['user_id' => $user->id, 'app' => $app]);

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

    private function generateToken(array $payload, bool $willExpire = true): string
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

    public function sendResetPasswordEmail(string $email, string $callbackUrl)
    {
        if (empty($email)) {
            throw new \Exception('Empty email address');
        }

        $userId = User::where('email', $email)->first()->id;

        $linkTtl = config('app.token.email_link_ttl_hours');
        $token = Token::create([
            'token' => Str::uuid()->toString(),
            'expired_at' => time() + $linkTtl * 3600,
            'type' => 'PASSWORD_RESET',
            'user_id' => $userId,
        ]);

        $url = $callbackUrl . "?token=" . $token->token;

        $rpc = new UesRpcClient(config('app.rpc.endpoint.notification'));
        try {
            $rpc->call(
                'sendMail',
                [
                    'content' => [
                        'to' => $email,
                        'subject' => '衡论科技 - 重置密码',
                        'text' => "请在{$linkTtl}小时内通过以下链接重置密码：$url",
                        'html' => "<span style='font-weight:bold'>请在<span style='color:red'>{$linkTtl}小时内</span>通过以下链接重置密码：</span><a href='$url'>$url</a>",
                    ]
                ]
            );
        }
        catch (\Exception $exception) {
            $token->delete();
            throw $exception;
        }
    }

    private function verifyToken(string $tokenVal, int $expiryTime = null)
    {
        if (is_null($expiryTime)) {
            $expiryTime = time();
        }
        $token = Token::where('token', $tokenVal)
                ->where('expired_at', '>', $expiryTime)
                ->get();
        if ($token->isEmpty()) {
            throw new AuthException("Token [$tokenVal] invalid", AuthException::CODE_INVALID_TOKEN);
        }
        return $token->first();
    }

    public function resetPassword(string $newPassword, string $tokenVal)
    {
        $token = $this->verifyToken($tokenVal);

        $user = User::findOrFail($token->user_id, ['id', 'password']);
        $user->password = Hash::make($newPassword);
        $user->save();

        Token::where('token', $tokenVal)->update([
            'expired_at' => -1
        ]);
    }

}
