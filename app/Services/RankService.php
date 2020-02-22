<?php


namespace App\Services;


use App\User;
use Illuminate\Support\Str;

class RankService
{
    public function updateRank(string $app, int $id, int $score, ?string $rankType = null)
    {
        if (!Str::endsWith($app, '_oppo')) {
            throw new \Exception("app ${app} not support updateRank operation", -1);
        }

        $user = new User();
        $user->id = $id;
        $openid = $user->getOpenid($app);

        if (!$openid) {
            throw new \Exception("userId[$id] has no openid in app[$app]", -1);
        }

        $driver = new OppoHelper($app);
        $driver->updateRank($openid, $score);
    }

}
