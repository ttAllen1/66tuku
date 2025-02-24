<?php

namespace Modules\Admin\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Api\Models\GameTransfer;
use Modules\Api\Models\User;
use Modules\Api\Models\UserGame;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\game\KyService;

class CheckTransfer extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:checkTransfer';      // 每月第一天 00:05 执行一次

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理游戏转入转出失败订单.';

    /**
     * Create a new command instance.
     *
     * @return void
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
        while (true) {
            $data = GameTransfer::query()->where('status', 0)->orderByDesc('id')->get();
            if ($data) {
                foreach ($data as $item) {
                    if ($item->type == 'transferIn') {
                        try {
                            $result = (new KyService())->transferIn($item->account, $item->amount, $item->order_no);
                            if (isset($result['code'])) {
                                User::query()->where('id', $item->user_id)->decrement('account_balance', $item->amount);
                                (new ActivityService())->modifyAccount($item->user_id, 26, $item->amount);
                                GameTransfer::query()->where('id', $item->id)->update(['status' => 1]);
                            }
                        } catch (Exception $exception) {
                            if ($exception->getMessage() == '34') {
                                try {
                                    User::query()->where('id', $item->user_id)->decrement('account_balance', $item->amount);
                                    (new ActivityService())->modifyAccount($item->user_id, 26, $item->amount);
                                    GameTransfer::query()->where('id', $item->id)->update(['status' => 1]);
                                } catch (Exception $exception) {
                                    Log::channel('ky_Transfer_Out')->info('CMD Amount TransferIn Error: ' . $item->account . ' => ' . $exception->getMessage());
                                }
                            } else {
                                if ($item->number == 4) {
                                    GameTransfer::query()->where('id', $item->id)->update(['status' => 1]);
                                } else {
                                    GameTransfer::query()->where('id', $item->id)->increment('number');
                                }
                            }
                        }
                    } else {
                        try {
                            $result = (new KyService())->checkTransferOut($item->account, $item->amount, $item->order_no);
                            if (isset($result['code'])) {
                                User::query()->where('id', $item->user_id)->increment('account_balance', $item->amount);
                                (new ActivityService())->modifyAccount($item->user_id, 27, $item->amount);
                                GameTransfer::query()->where('id', $item->id)->update(['status' => 1]);
                                UserGame::query()->where('user_id', $item->user_id)->update(['last_recharge_type' => 0]);
                            }
                        } catch (Exception $exception) {
                            if ($exception->getMessage() == '34') {
                                try {
//                                    User::query()->where('id', $item->user_id)->increment('account_balance', $item->amount);
//                                    (new ActivityService())->modifyAccount($item->user_id, 27, $item->amount);
                                    GameTransfer::query()->where('id', $item->id)->update(['status' => 1]);
//                                    UserGame::query()->where('user_id', $item->user_id)->update(['last_recharge_type' => 0]);
                                } catch (Exception $exception) {
                                    Log::channel('ky_Transfer_Out')->info('CMD Amount TransferOut Error: ' . $item->account . ' => ' . $exception->getMessage());
                                }
                            } else {
                                if ($item->number == 4) {
                                    GameTransfer::query()->where('id', $item->id)->update(['status' => 1]);
                                } else {
                                    GameTransfer::query()->where('id', $item->id)->increment('number');
                                }
                            }
                        }
                    }
                }
                echo "sleep 10 \n";
                sleep(10);
            } else {
                echo "sleep 10 \n";
                sleep(10);
            }
        }
    }

}
