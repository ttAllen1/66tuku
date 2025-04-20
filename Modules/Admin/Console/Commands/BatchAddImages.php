<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\LotteryTypeId;
use Symfony\Component\Console\Input\InputOption;

class BatchAddImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:batch-add-images';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '批量添加图片';

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
        try{
            DB::BeginTransaction();
            $existLotteryTypeIds = LotteryTypeId::query()
                ->where('lotteryType', 1)
                ->where('year', date('Y'))
                ->pluck('typeIds')->toArray();
            $existLotteryTypeIds = json_decode($existLotteryTypeIds[0], true);
            $date = date('Y-m-d H:i:s');
            for ($i = 880001; $i <= 880088; $i++) {
                if($i == 880028) {
                    continue;
                }
                $existLotteryTypeIds[] = $i;
                DB::table('index_pics')
                    ->insert([
                        'pictureTypeId' => $i,
                        'lotteryType'   => 1,
                        'color' => 1,
                        'sort'  => 122,
                        'is_add'    => 1,
                        'created_at' => $date,
                        'pictureName'   => '青蛙系列'.($i - 880001 + 1),
                    ]);
                DB::table('year_pics')
                    ->insert([
                        'pictureTypeId' => $i,
                        'lotteryType'   => 1,
                        'year' => date('Y'),
                        'color' => 1,
                        'pictureName'   => '青蛙系列'.($i - 880001 + 1),
                        'max_issue' => 43,
                        'issues'    => '["\u7b2c43\u671f","\u7b2c42\u671f","\u7b2c41\u671f","\u7b2c40\u671f","\u7b2c39\u671f","\u7b2c38\u671f","\u7b2c37\u671f","\u7b2c36\u671f","\u7b2c35\u671f","\u7b2c34\u671f","\u7b2c33\u671f","\u7b2c32\u671f","\u7b2c31\u671f","\u7b2c30\u671f","\u7b2c29\u671f","\u7b2c28\u671f","\u7b2c27\u671f","\u7b2c26\u671f","\u7b2c25\u671f","\u7b2c24\u671f","\u7b2c23\u671f","\u7b2c22\u671f","\u7b2c21\u671f","\u7b2c20\u671f","\u7b2c19\u671f","\u7b2c18\u671f","\u7b2c17\u671f","\u7b2c16\u671f","\u7b2c15\u671f","\u7b2c14\u671f","\u7b2c13\u671f","\u7b2c12\u671f","\u7b2c11\u671f","\u7b2c10\u671f","\u7b2c9\u671f","\u7b2c8\u671f","\u7b2c7\u671f","\u7b2c6\u671f","\u7b2c5\u671f","\u7b2c4\u671f","\u7b2c3\u671f","\u7b2c2\u671f","\u7b2c1\u671f"]',
                        'keyword' => $i,
                        'letter' => 'Q',
                        'is_add'    => 1,
                        'created_at' => $date
                    ]);
            }
            $existLotteryTypeIds = json_encode($existLotteryTypeIds);
            LotteryTypeId::query()
                ->where('year', date('Y'))
                ->where('lotteryType', 1)
                ->update([
                    'typeIds' => $existLotteryTypeIds,
                    'updated_at' => $date
                ]);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            dd($exception->getMessage(), $exception->getLine());
        }
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
                if ($v['thumbUpCount'] >= $vv[0] && ($v['thumbUpCount'] < $vv[1] || $vv[1] == '~')) { // 缺少对$vv[1]=~的考虑
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
                $goldData[$k]['balance'] = $userInitMoney[$v['user_id']] + $v['money'];
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
    protected function extractedRead(Collection $res, $read_min_user_level, $read_num_area, $lotteryType, $issue, $type): bool
    {
        if ($res->isEmpty()) {
            return false;
        }

        // 拿原作者id
        $target_ids = $res->pluck('target_id')->toArray();
        if ($type == 3) { // 阅读论坛
            $user_ids = DB::table('discusses')->whereIn('id', $target_ids)->select([
                'id', 'user_id'
            ])->get()->map(function ($item) {
                return (array)$item;
            })->toArray();
        } else { // 阅读发现
            $user_ids = DB::table('user_discoveries')->whereIn('id', $target_ids)->select([
                'id', 'user_id'
            ])->get()->map(function ($item) {
                return (array)$item;
            })->toArray();
        }
//        $user_ids = (array)$user_ids;
        $res = $res->toArray();
        foreach ($res as $k => $v) {
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
                if ($v['count_target'] >= $vv[0] && ($v['thumbUpCount'] < $vv[1] || $vv[1] == '~')) {
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
                $goldData[$k]['balance'] = $userInitMoney[$v['user_id']] + $v['money'];
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
