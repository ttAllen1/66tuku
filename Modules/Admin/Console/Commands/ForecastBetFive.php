<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\HistoryNumber;
use Modules\Common\Services\BaseService;
use Swoole\Coroutine;
use Swoole\Process;
use Symfony\Component\Console\Input\InputOption;

class ForecastBetFive extends Command
{

    protected $_assocNums = []; // 当前年生肖-号码对应关系

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:forecast-bets-five';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '实时开奖数据.';

    protected $a = true;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle1()
    {
        try {
            for ($i = 1; $i <= 7; $i++) {
                $process = new Process(function () use ($i) {
                    go(function () use ($i) {
                        while (true) {
                            try {
                                // 今天开奖是否完成
                                $isOpen = Redis::get("lottery_real_open_over_" . $i . "_with_" . date("Y-m-d"));
                                if (!$isOpen) {
                                    Coroutine::sleep(2);
                                    continue;
                                }
                                try {
                                    $numbersInfo = HistoryNumber::query()
                                        ->where('year', date("Y"))
                                        ->where('lotteryType', $i)
                                        ->orderByDesc('year')
                                        ->orderByDesc('issue')
                                        ->select(['issue', 'number', 'number_attr'])
                                        ->firstOrFail()->toArray();
                                } catch (ModelNotFoundException $exception) {
                                    Coroutine::sleep(2);
                                    continue;
                                }
                                $bets = $this->getBets($i, $numbersInfo['issue'], $numbersInfo);
                                if (!$bets) {
                                    Coroutine::sleep(2);
                                    continue;
                                }

                                Coroutine::sleep(2); // 休眠1秒
                            } catch (\Exception $exception) {
                                Log::error('开奖失败', ['message' => $exception->getMessage()]);
                                continue;
                            }
                        }
                    });
                });
                // 启动进程
                $process->start();
            }

            // 等待所有进程结束
            Process::wait(true);
        } catch (\Exception $exception) {
            return;
        }

    }

    public function handle()
    {
        $lotteryType = 5;
        while (true) {
            try {
                // 检查当天是否已经完成开奖
                $isOpen = Redis::get("lottery_real_open_over_" . $lotteryType . "_with_" . date("Y-m-d"));
                if (!$isOpen) {
                    sleep(2); // 等待 2 秒后继续检查
                    continue;
                }
                // 获取开奖号码信息
                try {
                    $numbersInfo = HistoryNumber::query()
                        ->where('year', date("Y"))
                        ->where('lotteryType', $lotteryType)
                        ->orderByDesc('year')
                        ->orderByDesc('issue')
                        ->select(['issue', 'number', 'number_attr'])
                        ->firstOrFail()
                        ->toArray();
                } catch (ModelNotFoundException $exception) {
                    sleep(2); // 如果数据未找到，休眠 2 秒后重试
                    continue;
                }
                // 获取投注信息
                $this->getBets($lotteryType, $numbersInfo['issue'], $numbersInfo);
                sleep(2);
            } catch (\Exception $exception) {
                Log::error('开奖失败', ['message' => $exception->getMessage()]);
                continue;
            }
        }
    }

    protected function getBets($lotteryType, $issue, $numbersInfo): bool
    {
        try {
            DB::beginTransaction();
            (new BaseService())->getBets($lotteryType, $issue, $numbersInfo);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
        }
        return true;
        $issue = (int)str_replace(date("Y"), '', $issue);
        $res = DB::table("user_bets")
            ->lockForUpdate()
            ->where('year', date("Y"))
            ->where('issue', $issue)
            ->where('lotteryType', $lotteryType)
            ->where('status', 0)
            ->select(['id', 'forecast_bet_name', 'user_id', 'each_bet_money', 'bet_num', 'odd'])
            ->get()
            ->map(function ($item) {
                return (array)$item;
            })
            ->toArray();
        $data = [];
        if (!$res) {
            return false;
        }
        foreach ($res as $v) {
            $data[$v['forecast_bet_name']][] = $v;
        }

        $teSize = $numbersInfo['number_attr'][6]['number'] > 24 ? '特大' : '特小';
        $teSingle = $numbersInfo['number_attr'][6]['number'] % 2 == 0 ? '特双' : '特单';
        $heSize = (floor(($numbersInfo['number_attr'][6]['number']) / 10) + ($numbersInfo['number_attr'][6]['number'] % 10)) > 6 ? '合大' : '合小';
        $heSingle = (floor(($numbersInfo['number_attr'][6]['number']) / 10) + ($numbersInfo['number_attr'][6]['number'] % 10)) % 2 == 0 ? '合双' : '合单';
        $datas = [];
        foreach ($data as $k => $v) {
            if ($k == '特码') {
                foreach ($v as $kk => $vv) {
                    if ($numbersInfo['number_attr'][6]['number'] == 49) {
                        $vv['status'] = 2;
                    } else if ($vv['bet_num'] == $teSize) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == $teSingle) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == $heSize) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == $heSingle) {
                        $vv['status'] = 1;
                    } else if ($vv['bet_num'] == '天肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->tian) ? 1 : -1;
                    } else if ($vv['bet_num'] == '地肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->di) ? 1 : -1;
                    } else if ($vv['bet_num'] == '家肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->jiaqin) ? 1 : -1;
                    } else if ($vv['bet_num'] == '野肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->yeshou) ? 1 : -1;
                    } else if ($vv['bet_num'] == '前肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->qian) ? 1 : -1;
                    } else if ($vv['bet_num'] == '后肖') {
                        $vv['status'] = in_array($numbersInfo['number_attr'][6]['shengXiao'], (new BaseService())->hou) ? 1 : -1;
                    } else if ($vv['bet_num'] == '红波') {
                        $vv['status'] = $numbersInfo['number_attr'][6]['color'] == 1 ? 1 : -1;
                    } else if ($vv['bet_num'] == '蓝波') {
                        $vv['status'] = $numbersInfo['number_attr'][6]['color'] == 2 ? 1 : -1;
                    } else if ($vv['bet_num'] == '绿波') {
                        $vv['status'] = $numbersInfo['number_attr'][6]['color'] == 3 ? 1 : -1;
                    } else {
                        $vv['status'] = -1;
                    }
                    $datas[] = $vv;
                }
            } else if ($k == '特肖') {
                foreach ($v as $kk => $vv) {
                    if ($vv['bet_num'] == $numbersInfo['number_attr'][6]['shengXiao']) {
                        $vv['status'] = 1;
                    } else {
                        $vv['status'] = -1;
                    }
                    $datas[] = $vv;
                }
            } else if ($k == '平特肖') {
                foreach ($v as $kk => $vv) {
                    if ($vv['bet_num'] == $numbersInfo['number_attr'][0]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][1]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][2]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][3]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][4]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][5]['shengXiao']
                        || $vv['bet_num'] == $numbersInfo['number_attr'][6]['shengXiao']
                    ) {
                        $vv['status'] = 1;
                    } else {
                        $vv['status'] = -1;
                    }
                    $datas[] = $vv;
                }
            }
        }
        if ($datas) {
            $loseIds = [];
            $drawIds = [];
            $winUserInfo = [];
            $winUserIds = [];
            $loseUserInfo = [];
            $drawUserInfo = [];
            $openType = (new BaseService())->getOpenType();
//            dd($openType);
            DB::beginTransaction();
            try {
                foreach ($datas as $k => $v) {
                    if ($v['status'] == -1) {
                        $loseIds[] = $v['id'];
                        $loseUserInfo[$k]['id'] = $v['id'];
                        $loseUserInfo[$k]['user_id'] = $v['user_id'];
                        $loseUserInfo[$k]['win_money'] = $v['each_bet_money'] * $v['odd'];  // 失败的金额
                    } else if ($v['status'] == 1) {
//                    $winIds[$v['id']] = [
//                        "win_money"     => $v['each_bet_money'] * $v['odd'],
//                        "status"        => 1,
//                    ];
                        DB::table('user_bets')->where('id', $v['id'])->update([
                            'status'     => 1,
                            'win_status' => $openType == 1 ? 2 : 1,
                            'win_money'  => $v['each_bet_money'] * $v['odd']
                        ]);
                        $winUserIds[] = $v['user_id'];
                        $winUserInfo[$k]['id'] = $v['id'];
                        $winUserInfo[$k]['user_id'] = $v['user_id'];
                        $winUserInfo[$k]['win_money'] = $v['each_bet_money'] * $v['odd'];  // 赢取的金额
                    } else if ($v['status'] == 2) {
                        $drawIds[] = $v['id'];
                        $drawUserInfo[$k]['id'] = $v['id'];
                        $drawUserInfo[$k]['user_id'] = $v['user_id'];
                        $drawUserInfo[$k]['win_money'] = $v['each_bet_money'];  // 退还的金额
                    }
                }

                if ($loseIds) {
                    DB::table('user_bets')->whereIn('id', $loseIds)->update([
                        'status'     => -1,
                        'win_status' => -1,
                    ]);
                }
                if ($drawIds) {
                    DB::table('user_bets')->whereIn('id', $drawIds)->update([
                        'status'     => 2,
                        'win_status' => -1,
                    ]);
                }

                if ($loseUserInfo) {  // 金币记录 余额 都不用动

                }
                if ($winUserInfo) {  // 金币记录 余额 都需要动
                    sort($winUserInfo);
                    foreach ($winUserInfo as $k => $v) {
                        if ($openType == 1) {
                            $userGoldData = [];
                            $userGoldData['user_id'] = $v['user_id'];
                            $userGoldData['type'] = 14;
                            $userGoldData['gold'] = $v['win_money'];
                            $userGoldData['symbol'] = '+';
                            $userGoldData['user_bet_id'] = $v['id'];
                            $userGoldData['balance'] = DB::table('users')->where('id', $v['user_id'])->value('account_balance') + $v['win_money'];
                            $userGoldData['created_at'] = date("Y-m-d H:i:s");
                            DB::table('user_gold_records')->insert($userGoldData);
                            DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']);
                        }
                    }
                }
                if ($drawUserInfo) {  //  余额 动
                    sort($drawUserInfo);
                    foreach ($drawUserInfo as $k => $v) {
                        $userGoldData = [];
                        $userGoldData['user_id'] = $v['user_id'];
                        $userGoldData['type'] = 14;
                        $userGoldData['gold'] = $v['win_money'];
                        $userGoldData['symbol'] = '+';
                        $userGoldData['user_bet_id'] = $v['id'];
                        $userGoldData['balance'] = DB::table('users')->where('id', $v['user_id'])->value('account_balance') + $v['win_money'];
                        $userGoldData['created_at'] = date("Y-m-d H:i:s");
                        DB::table('user_gold_records')->insert($userGoldData);
                        DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']);
                    }
//                    foreach ($drawUserInfo as $k => $v) {
//                        DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']); // 退还投注金额
//                    }
                }
            } catch (\Exception $exception) {
                DB::rollBack();
                Log::error('开奖失败', ['message' => $exception->getMessage()]);
            }
            DB::commit();
        }
        return true;
    }

    /**
     * 获取中奖金额到账方式
     * @return int
     */
    protected function getOpenType(): int
    {
        $type = Redis::get('forecast_bet_win_type');
        if (!$type) {
            $type = DB::table('auth_activity_configs')->where('k', 'forecast_bet_win_type')->value('v');
        }
        return $type ? (int)$type : 2;
    }



    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
