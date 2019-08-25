<?php

namespace App\Console\Commands;

use App\Services\NoticeService;
use App\Services\QuestionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckGamePay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-game-pay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check game pay';

    private $service;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::table('game_pay')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {

                }
            });
    }
}
