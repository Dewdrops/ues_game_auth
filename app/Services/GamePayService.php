<?php
/**
 * Created by PhpStorm.
 * User: dewdrops
 * Date: 2019-08-29
 * Time: 20:44
 */

namespace App\Services;


use App\Exceptions\GamePayException;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GamePayService
{

    const PAY_RECORD_TABLE = 'game_pay';

    public function getBalance(int $id, string $appName)
    {
        $driver = new TtHelper($appName);
        $user = User::findOrFail($id);

        $ret = $driver->getBalance($user);
        $balanceTt = $ret['balance'];

        $payRemaining = DB::table(self::PAY_RECORD_TABLE)
            ->where([
                'user_id' => $user->id,
                'app_name' => $appName,
                'type' => 'GAME_PAY',
                'processed' => false,
            ])
            ->sum('amount');

        return [
            'balance' => $balanceTt - $payRemaining,
        ];
    }

    public function gamePay(int $id, string $appName, int $amount, ?string $billNo)
    {
        $driver = new TtHelper($appName);
        $user = User::findOrFail($id);

        if ($billNo === null) {
            $billNo = Str::uuid()->toString();
        }

        $record = [
            'user_id' => $user->id,
            'app_name' => $appName,
            'type' => 'GAME_PAY',
            'amount' => $amount,
            'bill_no' => $billNo,
            'processed' => false,
        ];

        try {
            $ret = $driver->gamePay($user, $amount, $billNo);
        }
        catch (GamePayException $exception) {
            $record['extend_info'] = json_encode([
                'error' => $exception->getMessage(),
            ]);
            DB::table(self::PAY_RECORD_TABLE)->insert($record);

            throw $exception;
        }

        $record['processed'] = true;
        DB::table(self::PAY_RECORD_TABLE)->insert($record);

        return $ret;
    }
}
