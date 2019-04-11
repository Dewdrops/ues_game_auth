<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018/10/31
 * Time: 8:35 PM
 */

namespace App\Exceptions;


class AuthException extends \Exception
{
    const CODE_LOGIN_SESSION_EXPIRED = 22;
}