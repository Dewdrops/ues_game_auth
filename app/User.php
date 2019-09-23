<?php

namespace App;

use App\Exceptions\AuthException;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    const WX_CRED_TABLE = 'weapp';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

//        $this->table = config('app.db.user_table');
    }

    protected $guarded = [
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function tryInsert()
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
    }

    public static function byWxOpenid(string $appName, string $wxOpenid, array $fields)
    {
        $row = DB::table(self::WX_CRED_TABLE)
            ->where(['app_name' => $appName, 'wx_openid' => $wxOpenid])
            ->select(['user_id'])
            ->first();
        if ($row) {
            return User::find($row->user_id, $fields);
        }
        else {
            return null;
        }
    }

    public function getOpenid(string $appName)
    {
        $row = DB::table(self::WX_CRED_TABLE)
            ->where(['app_name' => $appName, 'user_id' => $this->id])
            ->select(['wx_openid'])
            ->first();
        if ($row) {
            return $row->wx_openid;
        }
        else {
            return null;
        }
    }

    public function saveWxCredentials(string $appName, string $wxOpenid)
    {
        $attrs = [
            'app_name' => $appName,
            'user_id' => $this->id,
        ];
        $row = array_replace([], $attrs);
        $row['wx_openid'] = $wxOpenid;
        try {
            DB::table(self::WX_CRED_TABLE)
                ->updateOrInsert($attrs, $row);
        }
        catch (\Exception $e) {
            if ($e->getCode() === "23505") {
                throw new AuthException(
                    "Duplicate wechat credentials",
                    AuthException::CODE_DUPLICATE_USERNAME
                );
            }
            throw $e;
        }
    }

    public function wxCredExisted(string $appName): bool
    {
        return DB::table(self::WX_CRED_TABLE)
                ->where(['user_id' => $this->id, 'app_name' => $appName])
                ->count() > 0;
    }

}
