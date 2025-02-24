<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\User;
use Modules\Admin\Models\ZanReadMoney;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Api\Models\Discuss;
use Modules\Common\Services\BaseService;
use Swoole\Coroutine;
use Swoole\Process;
use Symfony\Component\Console\Input\InputOption;

class ZanMoney extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:zan-money';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '开奖后统计点赞数、阅读数.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $lotteryTypes = [1, 2, 3, 4, 6];
        try{
            foreach ($lotteryTypes as $lotteryType) {
                $process = new Process(function () use ($lotteryType) {
                    go(function() use ($lotteryType) {
                        while(true) {
                            try{
                                // 今天开奖是否完成
                                $isOpen = Redis::get("lottery_real_open_over_".$lotteryType."_with_".date("Y-m-d"));
                                if (!$isOpen) {
//                                    Coroutine::sleep(5);
//                                    continue;
                                }

                                try{
                                    // 确保数据已入数据库
                                    $currentIssue = HistoryNumber::query()
                                        ->where('year', date("Y"))
                                        ->where('lotteryType', $lotteryType)
                                        ->orderByDesc('year')
                                        ->orderByDesc('issue')
                                        ->value('issue');
//                                    dd($currentIssue);
                                    if (!$currentIssue) {
                                        Coroutine::sleep(5);
                                        continue;
                                    }
                                    $currentIssue = (int)str_replace(date("Y"), '', $currentIssue);
//                                    dd($currentIssue);
                                }catch (ModelNotFoundException $exception) {
                                    Coroutine::sleep(5);
                                    continue;
                                }
                                $openRes = DB::table('auth_activity_configs')
                                    ->whereIn('k', ['zan_area_open', 'read_area_open', 'zan_num_area', 'read_num_area', 'zan_min_user_level', 'read_min_user_level'])
                                    ->pluck('v', 'k')->toArray();
//                                dd($openRes);
                                // 判断是否已发放
                                for($j=1;$j<=4;$j++) { // 4
                                    $isExist = ZanReadMoney::query()
                                        ->where('lotteryType', $lotteryType)
                                        ->where('year', date('Y'))
                                        ->where('issue', $currentIssue)
                                        ->where("type", $j)
                                        ->exists();
//                                    dd($isExist);
                                    if ($isExist) {     // 当前期已处理完成
                                        Coroutine::sleep(5);
                                        continue;
                                    }
                                    if ($j==1) { // 点赞论坛
                                        if ($openRes['zan_area_open'] != 1) {
                                            Coroutine::sleep(5);
                                            continue;
                                        }
                                        $zanDiscusses = $this->zanDiscusses($lotteryType, $currentIssue, $openRes);
                                    }
                                    if ($j==2) { // 点赞发现
                                        if ($openRes['zan_area_open'] != 1) {
                                            Coroutine::sleep(5);
                                            continue;
                                        }
                                        $zanDiscover = $this->zanDiscover($lotteryType, $currentIssue, $openRes);
                                    }
                                    if ($j==3) { // 阅读论坛
                                        if ($openRes['read_area_open'] != 1) {
                                            Coroutine::sleep(5);
                                            continue;
                                        }
                                        $readDiscover = $this->readDiscusses($lotteryType, $currentIssue, $openRes);
                                    }
                                    if ($j==4) { // 阅读发现
                                        if ($openRes['read_area_open'] != 1) {
                                            Coroutine::sleep(5);
                                            continue;
                                        }
                                        $readDiscover = $this->readDiscover($lotteryType, $currentIssue, $openRes);
                                    }
                                }

                                Coroutine::sleep(60); // 休眠1秒
                            }catch (\Exception $exception) {
                                Log::error('开奖失败', ['message'=>$exception->getMessage()]);
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
        }catch (\Exception $exception) {
            return;
        }

    }


    protected function zanDiscusses($lotteryType, $issue, $openRes): bool
    {
        $zan_num_area = unserialize($openRes['zan_num_area']);  // 点赞收益阶梯区间
//        dd($zan_num_area);
//        exit(1);
        $minNum = $zan_num_area[0][0] ?? 0;
        $res = DB::table('discusses')
            ->where('lotteryType', $lotteryType)
            ->where('issue', $issue)
            ->where('year', date('Y'))
            ->where('status', 1)
            ->where('thumbUpCount', '>=', $minNum)
            ->select(['id', 'user_id', 'lotteryType', 'thumbUpCount'])
            ->get()
            ->map(function($item) {
                return (array)$item;
            }); // 60017->61408
//        dd($res);
//        switch ($lotteryType){
//            case 1:
//                $goldType = 20;
//                break;
//            case 2:
//                $goldType = 22;
//                break;
//            case 3:
//                $goldType = 24;
//                break;
//            case 4:
//                $goldType = 26;
//                break;
//            case 5:
//                $goldType = 28;
//                break;
//        }
        return $this->extractedZan($res, $openRes['zan_min_user_level'], $zan_num_area, $lotteryType, $issue, 1);
    }
    protected function zanDiscover($lotteryType, $issue, $openRes): bool
    {
        $zan_num_area = unserialize($openRes['zan_num_area']);
        $minNum = $zan_num_area[0][0] ?? 0;
        $res = DB::table('user_discoveries')
            ->where('lotteryType', $lotteryType)
            ->where('issue', $issue)
            ->where('year', date('Y'))
            ->where('status', 1)
            ->where('thumbUpCount', '>=', $minNum)
            ->select(['id', 'user_id', 'lotteryType', 'thumbUpCount'])
            ->get()
            ->map(function($item) {
                return (array)$item;
            });
//        dd($res);
        /*switch ($lotteryType){
            case 1:
                $goldType = 21;
                break;
            case 2:
                $goldType = 23;
                break;
            case 3:
                $goldType = 25;
                break;
            case 4:
                $goldType = 27;
                break;
            case 5:
                $goldType = 29;
                break;
        }*/
        return $this->extractedZan($res, $openRes['zan_min_user_level'], $zan_num_area, $lotteryType, $issue, 2);
    }

    public function readDiscusses($lotteryType, $issue, $openRes): bool
    {
        $read_num_area = unserialize($openRes['read_num_area']);
        $minNum = $read_num_area[0][0] ?? 0;
//        dd($minNum);
        $res = DB::table('user_reads')
            ->where('lotteryType', $lotteryType)
            ->where('issue', $issue)
            ->where('year', date('Y'))
            ->where('type', 1)
            ->select(['id', 'user_id', 'target_id', DB::raw("count(target_id) as count_target")])
            ->groupBy('target_id')
            ->having('count_target', '>=', $minNum)
            ->get()
            ->map(function($item) {
                return (array)$item;
            });
//        dd($res);
//        switch ($lotteryType){
//            case 1:
//                $goldType = 30;
//                break;
//            case 2:
//                $goldType = 32;
//                break;
//            case 3:
//                $goldType = 34;
//                break;
//            case 4:
//                $goldType = 36;
//                break;
//            case 5:
//                $goldType = 38;
//                break;
//        }
        return $this->extractedRead($res, $openRes['read_min_user_level'], $read_num_area, $lotteryType, $issue, 3);
    }

    public function readDiscover($lotteryType, $issue, $openRes): bool
    {
        $read_num_area = unserialize($openRes['read_num_area']);
        $minNum = $read_num_area[0][0] ?? 0;
        $res = DB::table('user_reads')
            ->where('lotteryType', $lotteryType)
            ->where('issue', $issue)
            ->where('year', date('Y'))
            ->where('type', 2)
            ->select(['id', 'user_id', 'target_id', DB::raw("count(target_id) as count_target")])
            ->groupBy('target_id')
            ->having('count_target', '>=', $minNum)
            ->get()
            ->map(function($item) {
                return (array)$item;
            });
//        switch ($lotteryType){
//            case 1:
//                $goldType = 31;
//                break;
//            case 2:
//                $goldType = 33;
//                break;
//            case 3:
//                $goldType = 35;
//                break;
//            case 4:
//                $goldType = 37;
//                break;
//            case 5:
//                $goldType = 39;
//                break;
//        }
        return $this->extractedRead($res, $openRes['read_min_user_level'], $read_num_area, $lotteryType, $issue, 4);
    }

    public function combine($data): array
    {
        $idArr = [];
        foreach ($data as $datum) {
            if (!isset($idArr[$datum['user_id']])) {
                $idArr[$datum['user_id']] = $datum;
            } else {
                $idArr[$datum['user_id']]["money"] += $datum['money'];
            }
        }
        sort($idArr);
        return $idArr;
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

    /**
     * @param Collection $res
     * @param $zan_min_user_level
     * @param $zan_num_area
     * @param $lotteryType
     * @param $issue
     * @param $type
     * @param $goldType
     * @return bool
     */
    protected function extractedZan(Collection $res, $zan_min_user_level, $zan_num_area, $lotteryType, $issue, $type): bool
    {
        if ($res->isEmpty()) {

            return false;
        }
//        dd($zan_min_user_level);
        // 再对user_id进行过滤
        $user_ids = $res->pluck('user_id')->toArray();  // 论坛的发帖者
        $user_ids = DB::table('users')
            ->whereIn('id', $user_ids)
            ->where('growth_score', '>=', $zan_min_user_level)
            ->pluck('id')
            ->toArray();
        if (!$user_ids) {
            return false;
        }
//        dd($user_ids);
        $user_ids = [75454];
        // 计算每条论坛点赞数量实际对应的金币数
        $res = $res->toArray();
        $data = [];
        $userIds = [];
        foreach ($res as $k => $v) {
            if (!in_array($v['user_id'], $user_ids)) { // 排除等级不够的用户
                continue;
            }
            foreach ($zan_num_area as $kk => $vv) {
                if ($v['thumbUpCount'] >= $vv[0] && ($v['thumbUpCount'] < $vv[1] || $vv[1]=='~')) { // 缺少对$vv[1]=~的考虑
                    $userIds[] = $v['user_id'];
                    $data[$k]['user_id'] = $v['user_id'];
                    $data[$k]['lotteryType'] = $lotteryType;
                    $data[$k]['money'] = number_format($vv[2] + ($v['thumbUpCount'] * $vv[3]) / 100, 2);
                }
            }
        }
//        dd($data);
        if (!$data) {
            return false;
        }

        $userInitMoney = DB::table('users')
            ->whereIn('id', $userIds)
            ->pluck('account_balance', 'id')
            ->toArray();
        // 将相同user_id组合到一起
        $data = $this->combine($data);
//        dd($data);
        // 插入到用户余额 和 金币记录表 将该彩种期数记录表对应表中
        $goldData = [];
        try {
            DB::beginTransaction();
//            dd($data);
            foreach ($data as $k => $v) {
                $zanId = DB::table('zan_read_money')->insertGetId([
                    'user_id'     => $v['user_id'],
                    'money'       => $v['money'],
                    'year'        => date('Y'),
                    'issue'       => $issue,
                    'lotteryType' => $lotteryType,
                    'type'        => $type,
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
                DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['money']);
                $goldData[$k]['user_id'] = $v['user_id'];
//                $goldData[$k]['type'] = $goldType;
                $goldData[$k]['type'] = 21;
                $goldData[$k]['user_post_id'] = $zanId;
                $goldData[$k]['gold'] = $v['money'];
                $goldData[$k]['balance'] = $userInitMoney[$v['user_id']]+$v['money'];
                $goldData[$k]['symbol'] = '+';
                $goldData[$k]['created_at'] = date('Y-m-d H:i:s');
            }
//            dd($goldData);
            DB::table('user_gold_records')->insert($goldData);

            DB::commit();
        } catch (\Exception $exception) {
//            dd($exception->getMessage());
            DB::rollBack();
            return false;
        }
        return true;
    }

    /**
     * @param Collection $res
     * @param $read_min_user_level
     * @param $read_num_area
     * @param $lotteryType
     * @param $issue
     * @param $type
     * @param $goldType
     * @return bool
     */
    protected function extractedRead(Collection $res, $read_min_user_level, $read_num_area, $lotteryType, $issue,  $type): bool
    {
        if ($res->isEmpty()) {
            return false;
        }

        // 拿原作者id
        $target_ids = $res->pluck('target_id')->toArray();
        if ($type == 3) { // 阅读论坛
            $user_ids = DB::table('discusses')->whereIn('id', $target_ids)->select(['id', 'user_id'])->get()->map(function($item) {
                return (array)$item;
            })->toArray();
        } else { // 阅读发现
            $user_ids = DB::table('user_discoveries')->whereIn('id', $target_ids)->select(['id', 'user_id'])->get()->map(function($item) {
                return (array)$item;
            })->toArray();
        }
//        $user_ids = (array)$user_ids;
        $res = $res->toArray();
        foreach($res as $k => $v) {
            foreach ($user_ids as $kk => $vv) {
                if ($v['target_id'] == $vv['id']) {
                    $res[$k]['user_id'] = $vv['user_id'];
                }
            }
        }
//        dd($res, $user_ids);
        foreach ($user_ids as $k => $v) {
            $user_ids[$k] = $v['user_id'];
        }
//        dd($user_ids);
        // 再对user_id进行过滤
//        $user_ids = $res->pluck('user_id')->toArray();
        $user_ids = DB::table('users')
            ->whereIn('id', $user_ids)
            ->where('growth_score', '>=', $read_min_user_level)
            ->pluck('id')
            ->toArray();

        if (!$user_ids) {
            return false;
        }
        $user_ids = [75454];
        // 计算每条论坛点赞数量实际对应的金币数
//        $res = $res->toArray();
        $data = [];
        $userIds = [];
        foreach ($res as $k => $v) {
            if (!in_array($v['user_id'], $user_ids)) { // 排除等级不够的用户
                continue;
            }
            foreach ($read_num_area as $kk => $vv) {
                if ($v['count_target'] >= $vv[0] && ($v['thumbUpCount'] < $vv[1] || $vv[1]=='~')) {
                    $userIds[] = $v['user_id'];
                    $data[$k]['user_id'] = $v['user_id'];
                    $data[$k]['lotteryType'] = $lotteryType;
                    $data[$k]['money'] = number_format($vv[2] + ($v['count_target'] * $vv[3]) / 100, 2);
                }
            }
        }
        if (!$data) {
            return false;
        }
//        dd($data);
        // 获取每个用户的最初余额
        $userInitMoney = DB::table('users')
            ->whereIn('id', $userIds)
            ->pluck('account_balance', 'id')
            ->toArray();
        // 将相同user_id组合到一起
        $data = $this->combine($data);
        // 插入到用户余额 和 金币记录表 将该彩种期数记录表对应表中
        $goldData = [];
        try {
            DB::beginTransaction();
            foreach ($data as $k => $v) {
                $zanId = DB::table('zan_read_money')->insertGetId([
                    'user_id'     => $v['user_id'],
                    'money'       => $v['money'],
                    'year'        => date('Y'),
                    'issue'       => $issue,
                    'lotteryType' => $lotteryType,
                    'type'        => $type,
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
                DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['money']);
                $goldData[$k]['user_id'] = $v['user_id'];
//                $goldData[$k]['type'] = $goldType;
                $goldData[$k]['type'] = 21;
                $goldData[$k]['user_post_id'] = $zanId;
                $goldData[$k]['gold'] = $v['money'];
                $goldData[$k]['balance'] = $userInitMoney[$v['user_id']]+$v['money'];
                $goldData[$k]['symbol'] = '+';
                $goldData[$k]['created_at'] = date('Y-m-d H:i:s');
            }
            DB::table('user_gold_records')->insert($goldData);

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            return false;
        }
        return true;
    }
}
