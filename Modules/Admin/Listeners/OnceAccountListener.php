<?php

namespace Modules\Admin\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Events\OnceAccount;

class OnceAccountListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param OnceAccount $event
     * @return bool
     */
    public function handle(OnceAccount $event): bool
    {
        try{
            DB::beginTransaction();
            $res = $event->userBets;
            if (!$res) {
                return true;
            }
            Redis::set('once_account_adminId', 1);
            Redis::expire('once_account_adminId', 10*60);
            // 先计算出每个用户的余额增加数组
            $sums = [];
            $betIds = [];
            foreach ($res as $element) {
                $betIds[] = $element['id'];
                $userId = $element['user_id'];
                // 如果关联数组中已经有该 user_id，累加金额，否则初始化为当前金额
                if (isset($sums[$userId])) {
                    $sums[$userId] += $element['win_money'];
                } else {
                    $sums[$userId] = $element['win_money'];
                }
            }
            // 转换为最终的结果数组
            $userIds = [];
            foreach ($sums as $userId => $totalMoney) {
                $userIds[] = $userId;
                // 修改用户余额
                DB::table('users')->where('id', $userId)->increment('account_balance', $totalMoney);
            }
            // 把每个用户的初始余额查询出来
            $userBalance = DB::table('users')->whereIn('id', $userIds)->pluck('account_balance', 'id')->toArray();

            $outputArray = []; // 将用户id作为key，每个key包含相同用户的数据数组
            foreach ($res as $item) {
                $userId = $item["user_id"];
                if (!isset($outputArray[$userId])) {
                    // 如果 $outputArray 中没有该用户的记录，则创建一个
                    $outputArray[$userId] = [];
                }
                // 将当前元素追加到对应用户的记录中
                $outputArray[$userId][] = $item;
            }

            $userGoldData = [];
            foreach ($outputArray as $k => $v) { // 金币记录 余额 都需要动
                foreach($v as $kk => $vv) {
                    $userGoldData[$k][$kk]['user_id'] = $vv['user_id'];
                    $userGoldData[$k][$kk]['type'] = 14;
                    $userGoldData[$k][$kk]['gold'] = $vv['win_money'];
                    $userGoldData[$k][$kk]['symbol'] = '+';
                    $userGoldData[$k][$kk]['user_bet_id'] = $vv['id'];
                    $userGoldData[$k][$kk]['created_at'] = date("Y-m-d H:i:s");
                    if ($kk==0) {
                        $userGoldData[$k][$kk]['balance'] = $userBalance[$vv['user_id']] + $vv['win_money'];
                    } else {
                        $userGoldData[$k][$kk]['balance'] = $userGoldData[$k][$kk-1]['balance'] + $vv['win_money'];
                    }
                }
            }
            // 写入金币表
            foreach ($userGoldData as $v) {
                DB::table('user_gold_records')->insert($v);
            }
            // 更改投注表入账状态
            DB::table('user_bets')->whereIn('id', $betIds)->update(['win_status' => 2]);
            DB::commit();

            return true;
        }catch (\Exception $exception) {
            DB::rollBack();
            Redis::expire('once_account_adminId', 0);

            return false;
        }
    }
}

