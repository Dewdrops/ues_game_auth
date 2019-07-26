<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2018-12-26
 * Time: 10:11
 */

namespace App\Services;


use App\Exceptions\AuthException;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Log;

class WechatService
{

    private $app;

    function __construct()
    {
        $key = 'app.wechat.' . config('app.name');
        $this->app = Factory::miniProgram(config($key));
    }

    function session(string $code)
    {
        return $this->app->auth->session($code);
    }

    function decryptUserData($session, $iv, $encrypted)
    {
        return $this->app->encryptor->decryptData($session, $iv, $encrypted);
    }

}