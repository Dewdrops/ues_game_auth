<?php

namespace App;

use App\Exceptions\AuthException;
use Firebase\JWT\JWT;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('app.db.user_table');
    }

    protected $guarded = [
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function saveToken(): string
    {
        $payload = [
            'user_id' => $this->id,
            'exp' => time() + config('app.jwt.expiry_period')
        ];
        $jwtSecret = config('app.jwt.jwt_secret');
        $jwtAlg = config('app.jwt.jwt_alg');
        $this->token = JWT::encode($payload, $jwtSecret, $jwtAlg);
        $this->save();

        return $this->token;
    }

    public function tryInsert(bool $resetToken)
    {
        try {
            $this->save();
        }
        catch (\Exception $e) {
            if ($e->getCode() === "23505") {
                throw new AuthException(
                    $e->getMessage(),
                    AuthException::CODE_DUPLICATE_USERNAME
                );
            }
            throw $e;
        }
        if ($resetToken) {
            $this->saveToken();
        }
    }
}
