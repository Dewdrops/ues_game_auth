<?php

namespace App;

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
}
