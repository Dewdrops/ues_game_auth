<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2019-08-29
 * Time: 20:45
 */

namespace App\Services;


use App\User;

interface GamePayDriver
{
    function gamePay(User $user, int $amount, string $billNo);

    function getBalance(User $user);
}