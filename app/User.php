<?php

namespace App;

use App\Traits\BindingUserTable;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, BindingUserTable;

    protected $guarded = [
    ];

    protected $casts = [
        'data' => 'array'
    ];
}
