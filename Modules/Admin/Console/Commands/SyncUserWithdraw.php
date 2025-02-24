<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\UserPlatWithdraw;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SyncUserWithdraw extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:user-sync-withdraw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步用户提现信息，到用户表';

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
        $res = UserPlatWithdraw::query()
            ->where('status', 1)
//                ->take(10)
            ->select(['id', 'user_id', 'status', 'money'])
//                ->groupBy('status')
            ->get()
            ->toArray();

//            dd($res);
        // 使用关联数组保存每个 user_id 对应的总金额
        $sums = [];

        foreach ($res as $element) {
            $userId = $element['user_id'];

            // 如果关联数组中已经有该 user_id，累加金额，否则初始化为当前金额
            if (isset($sums[$userId])) {
                $sums[$userId] += $element['money'];
            } else {
                $sums[$userId] = $element['money'];
            }
        }

        // 转换为最终的结果数组
        $resultArray = [];
        foreach ($sums as $userId => $totalMoney) {
            $resultArray[] = ["user_id" => $userId, "total_money" => number_format($totalMoney, 2)];
        }
//            dd($resultArray);
        $enough100UserIds = [];
        $notEnough100UserIds = [];
        foreach ($resultArray as $k => $v) {
            if ($v['total_money']>=100) {
                $enough100UserIds[] = $v['user_id'];
            } else {
                $notEnough100UserIds[] = $v['user_id'];
            }
        }
//            dd($notEnough100UserIds);
        // 将超过100的用户的剩余额度置为0
//        DB::table('users')->whereIn('id', $enough100UserIds)->update([
//                'withdraw_lave_limit'   => 0
//        ]);
        // 第二次 跑成功的
        $res = UserPlatWithdraw::query()
            ->whereIn('status', [0, 1])
            ->select(['id', 'user_id', 'status', 'money'])
            ->whereIn('user_id', $notEnough100UserIds)
            ->where('user_id', '<>', 87124)
            ->get()
            ->toArray();

        $sums = [];

        foreach ($res as $element) {
            $userId = $element['user_id'];

            // 如果关联数组中已经有该 user_id，累加金额，否则初始化为当前金额
            if (isset($sums[$userId])) {
                $sums[$userId] += $element['money'];
            } else {
                $sums[$userId] = $element['money'];
            }
        }

        // 转换为最终的结果数组
        $resultArray = [];
        foreach ($sums as $userId => $totalMoney) {
            $resultArray[] = ["user_id" => $userId, "total_money" => number_format($totalMoney, 2)];
        }
        $data_101 = [];
        foreach ($resultArray as $k => $v) {
            DB::table('users')->where('id', $v['user_id'])->decrement('withdraw_lave_limit', $v['total_money']);
//            $resultArray[$k]['total_money'] = 100 -$v['total_money'];
//            if ($v['total_money']>=100) {
//                $data_101[] = $v['user_id'];
//            }
        }

        dd($resultArray);
    }

    public function wash_data($res)
    {

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
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
