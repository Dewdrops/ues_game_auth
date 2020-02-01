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
    const CODE_AUTH_FAILED = 21;
    const CODE_SESSION_EXPIRED = 22;

    const CODE_PASSWORD_WRONG = 24;

    const CODE_DUPLICATE_USERNAME = 25;
    const CODE_DUPLICATE_BIND = 26;
    const CODE_INVALID_TOKEN = 27;
}
