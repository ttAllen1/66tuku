<?php

namespace Modules\Admin\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Events\ReopenAccount;
use Modules\Admin\Models\HistoryNumber;
use Modules\Common\Services\BaseService;

class ReopenAccountListener implements ShouldQueue
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
     * @param ReopenAccount $event
     * @return bool
     */
    public function handle(ReopenAccount $event): bool
    {
        try{
            $res = $event->userBets;
            $mysqlIssue = $event->mysqlIssue;
            $year = $event->year;
            $lotteryType = $event->lotteryType;
            Redis::set('reopen_account_adminId_'.$lotteryType, 1);
            Redis::expire('reopen_account_adminId_'.$lotteryType, 3600);
            DB::beginTransaction();
            $noWinIds = [];// 未中奖id

            $winsIn = [];// 中奖金额已入用户账户
            $winsInIds = [];// 中奖金额已入用户账户id
            $winsInUserIds = [];// 中奖金额已入用户账户：用户id

            $winsNotIn = [];// 中奖金额未入用户账户
            $winsNotInIds = [];// 中奖金额未入用户账户：id

            $drawIn = []; // 和局用户账户
            $drawInIds = []; // 和局用户账户id
            $drawInUserIds = [];// 和局：用户id

            foreach($res as $k => $v) {
                if ($v['status'] == -1) {
                    $noWinIds[] = $v['id'];
                } else if ($v['status'] == 1 && $v['win_status']==2) {
                    $winsIn[] = $v;
                    $winsInIds[] = $v['id'];
                    $winsInUserIds[] = $v['user_id'];
                } else if ($v['status'] == 1 && $v['win_status']==1) {
                    $winsNotIn[] = $v;
                    $winsNotInIds[] = $v['id'];
                } else if ($v['status']==2){ // 和局
//                    $drawIn[] = $v;
//                    $drawInIds[] = $v['id'];
//                    $drawInUserIds[] = $v['user_id'];
                }
            }
            unset($res);
            if ($noWinIds) { // 未中奖：余额不动，金币记录不动 投注记录更改状态
                DB::table('user_bets')->whereIn('id', $noWinIds)->update([
                    'status'        => 0,
                    'win_status'    => 0,
                    'win_money'     => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }
            if ($drawIn) {      // 和局：余额动，金币记录也动
//                foreach ($drawIn as $k => $v) {
//                    DB::table('users')->where('id', $v['user_id'])->decrement('account_balance', $v['each_bet_money']);
//                    //DB::table('user_bets')->where('id', $v['id'])->decrement('account_balance', $v['each_bet_money']);
//                    DB::table('user_gold_records')->insert([
//                        'user_id'     => $v['user_id'],
//                        'type'        => 16,
//                        'gold'        => $v['each_bet_money'],
//                        'symbol'      => '-',
//                        'user_bet_id' => $v['id'],
//                        'created_at'  => date('Y-m-d H:i:s'),
//                        'balance'     => DB::table('users')->where('id', $v['user_id'])->value('account_balance')
//                    ]);
//                }
//                DB::table('user_bets')->whereIn('id', $drawInIds)->update([
//                    'status'        => 0,
//                    'win_status'    => 0,
//                    'win_money'     => 0,
//                    'updated_at'    => date('Y-m-d H:i:s')
//                ]);
            }
            //类型：1帖子点赞；2评论帖子；3转发帖子；4在线时长；5补填邀请码；6注册金币；7分享好友；8签到；
            //9充值（平台->图库）；10提现（plat_withdraw）；11福利；12撤回【充值】；13竞猜投注（下单）；
            //14竞猜投注（中奖｜未中奖｜平局）;15竞猜投注（撤单）无id；
            //16竞猜投注（系统撤回）无id
            if ($winsIn) { // 中奖金额已入用户账户：余额减少，新增金币减少记录（系统撤回-）投注记录更改状态
                // 查询出每个用户的初始金额
                $userBalance = DB::table('users')->whereIn('id', array_unique($winsInUserIds))->pluck('account_balance', 'id')->toArray();
                // 先计算出每个用户的余额增加数组
                $sums = []; // 用户和其对应总盈利的集合
                foreach ($winsIn as $element) {
                    $userId = $element['user_id'];
                    // 如果关联数组中已经有该 user_id，累加金额，否则初始化为当前金额
                    if (isset($sums[$userId])) {
                        $sums[$userId] += $element['win_money'];
                    } else {
                        $sums[$userId] = $element['win_money'];
                    }
                }
//            dd($sums, $userBalance);
                // 转换为最终的结果数组
                foreach ($sums as $userId => $totalMoney) {
                    // 修改用户余额
                    DB::table('users')->where('id', $userId)->decrement('account_balance', $totalMoney);
                }

                $outputArray = []; // 将用户id作为key，每个key包含相同用户的数据数组
                foreach ($winsIn as $item) {
                    $userId = $item["user_id"];
                    if (!isset($outputArray[$userId])) {
                        // 如果 $outputArray 中没有该用户的记录，则创建一个
                        $outputArray[$userId] = [];
                    }
                    // 将当前元素追加到对应用户的记录中
                    $outputArray[$userId][] = $item;
                }
                $userGoldData = [];
                foreach ($outputArray as $k => $v) { // 金币记录
                    foreach($v as $kk => $vv) {
                        $userGoldData[$k][$kk]['user_id'] = $vv['user_id'];
                        $userGoldData[$k][$kk]['type'] = 16;
                        $userGoldData[$k][$kk]['gold'] = $vv['win_money'];
                        $userGoldData[$k][$kk]['symbol'] = '-';
                        $userGoldData[$k][$kk]['user_bet_id'] = $vv['id'];
                        $userGoldData[$k][$kk]['created_at'] = date("Y-m-d H:i:s");
                        if ($kk==0) {
                            $userGoldData[$k][$kk]['balance'] = $userBalance[$vv['user_id']] - $vv['win_money'];
                        } else {
                            $userGoldData[$k][$kk]['balance'] = $userGoldData[$k][$kk-1]['balance'] - $vv['win_money'];
                        }
                    }
                }
//                dd($userGoldData);
                // 写入金币表
                foreach ($userGoldData as $v) {
                    DB::table('user_gold_records')->insert($v);
                }
                DB::table('user_bets')->whereIn('id', $winsInIds)->update([
                    'status'        => 0,
                    'win_status'    => 0,
                    'win_money'     => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
//            dd($userBalance, $winsIn, $outputArray);
            }
            if ($winsNotIn) { // 中奖金额未入用户账户：余额不动，金币记录不动 投注记录更改状态且win_money置0
                DB::table('user_bets')->whereIn('id', $winsNotInIds)->update([
                    'status'        => 0,
                    'win_status'    => 0,
                    'win_money'     => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }
            // 开启开奖
            $issue = str_pad($mysqlIssue, 3, 0, STR_PAD_LEFT);

            // 当前开奖时间 期数 号码
            $numbersInfo = HistoryNumber::query()->where('year', $year)->where('lotteryType', $lotteryType)->where('issue', $issue)->select(['number', 'number_attr'])->firstOrFail()->toArray();
            (new BaseService())->getBets($lotteryType, $issue, $numbersInfo);
            DB::commit();

            return true;
        }catch (\Exception $exception) {
            DB::rollBack();
            Redis::expire('reopen_account_adminId_'.$lotteryType, 0);
            return false;
        }
    }
}

