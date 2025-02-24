<?php

namespace Modules\Api\Services\income;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\ZanReadMoney;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\IncomeApply;
use Modules\Api\Models\UserPlatform;
use Modules\Api\Models\UserReward;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\platform\PlatformService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class IncomeService extends BaseApiService
{
    /**
     * 申请
     * @return JsonResponse
     */
    public function apple(): JsonResponse
    {
        $userId = auth('user')->id();
        $apply = IncomeApply::query()->where('user_id', $userId)->first();
        if ($apply && $apply->status == 1) {
            return $this->apiSuccess('申请已通过，请勿再次申请');
        } else {
            IncomeApply::query()->updateOrCreate([
                'user_id' => $userId,
            ], ['status' => 0]);
            return $this->apiSuccess('申请成功');
        }
    }

    /**
     * 打赏
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function reward($data): JsonResponse
    {
        try {
            $config = AuthActivityConfig::val([
                'discover_reward_open', 'discuss_reward_open', 'diagram_reward_open', 'reward_gold_section',
                'zan_num_area'
            ]);
//            dd(unserialize($config['zan_num_area']));
            if ($data['type'] == 1 && !$config['discover_reward_open']) {
                throw new CustomException(['message' => '发现打赏未开启']);
            }
            if ($data['type'] == 2 && !$config['discuss_reward_open']) {
                throw new CustomException(['message' => '论坛打赏未开启']);
            }
            if ($data['type'] == 3 && !$config['diagram_reward_open']) {
                throw new CustomException(['message' => '图解打赏未开启']);
            }
            $reward_gold_section = $config['reward_gold_section'] ?? 0;
            if ($reward_gold_section) {
                $reward_gold_section = unserialize($reward_gold_section);
                if ($reward_gold_section[1] != 0) {
                    if ($data['reward_money'] < $reward_gold_section[0] || $data['reward_money'] > $reward_gold_section[1]) {
                        throw new CustomException(['message' => '打赏金额在' . $reward_gold_section[0] . '到' . $reward_gold_section[1] . '之间']);
                    }
                }
            }
//            dd($config, $reward_gold_section);
            $this->checkRewardPerson($data);
            $this->checkBeRewardPerson($data['target_user_id']);
            $userId = auth('user')->id();
            DB::beginTransaction();
            $userReward = UserReward::query()->lockForUpdate()->create([
                'user_id'      => $userId,
                'be_user_id'   => $data['target_user_id'],
                'cate'         => 1,
                'type'         => $data['type'],
                'lotteryType'  => $data['lotteryType'],
                'issue'        => $data['issue'],
                'year'         => $data['year'],
                'reward_money' => $data['reward_money'],
                'target_id'    => $data['target_id'],
                'status'       => 1,
                'created_at'   => date('Y-m-d H:i:s')
            ]);
            // 获取打赏者和被打赏者的初始余额
            $userInitMoney = DB::table('users')
                ->whereIn('id', [$userId, $data['target_user_id']])
                ->pluck('account_balance', 'id')
                ->toArray();
            // 扣取打赏人余额
            DB::table('users')->lockForUpdate()->where('id', $userId)->decrement('account_balance', $data['reward_money']);
            // 增加被打赏人余额
            DB::table('users')->lockForUpdate()->where('id', $data['target_user_id'])->increment('account_balance', $data['reward_money']);
            // 记录到金币表

            /**
             * 类型：19五福红包领取;20港彩点赞论坛收益；21港彩点赞发现收益；
             *      22新澳彩点赞论坛收益；23新澳彩点赞发现收益；24台彩点赞论坛收益；
             *      25台彩点赞发现收益；26新彩点赞论坛收益；27新彩点赞发现收益；28老澳彩点赞论坛收益；
             *      29老澳彩点赞发现收益；30港彩阅读论坛收益；31港彩阅读发现收益；32新澳彩阅读论坛收益；
             *      33新澳彩阅读发现收益；34台彩阅读论坛收益；35台彩阅读发现收益；36新彩阅读论坛收益；
             *      37新彩阅读发现收益；38老澳彩阅读论坛收益；39老澳彩阅读发现收益；40快乐8 阅读论坛收益；41快乐8阅读发现收益
             * ALTER TABLE `lot_user_gold_records` ADD `user_reward_id` INT NOT NULL DEFAULT '0' COMMENT 'type=20有效，用户打赏表id' AFTER `user_welfare_id`;
             * ALTER TABLE `lot_user_gold_records` ADD `user_post_id` INT(11) NOT NULL DEFAULT '0' COMMENT 'type=21有效，用户赞、阅读表id' AFTER `user_reward_id`;
             */
            foreach ([$userId, $data['target_user_id']] as $k => $v) {
                $goldData[$k]['user_id'] = $v;
                $goldData[$k]['type'] = 20;
                $goldData[$k]['gold'] = $data['reward_money'];
                $goldData[$k]['balance'] = $v == $userId ? ($userInitMoney[$v] - $data['reward_money']) : ($userInitMoney[$v] + $data['reward_money']);
                $goldData[$k]['symbol'] = $v == $userId ? '-' : '+';
                $goldData[$k]['user_reward_id'] = $userReward->id;
                $goldData[$k]['created_at'] = date('Y-m-d H:i:s');
            }
//        dd($goldData);

            DB::table('user_gold_records')->insert($goldData);
            DB::commit();
            return $this->apiSuccess('打赏成功');
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception instanceof CustomException) {
                return $this->apiError($exception->getMessage());
            } else {
                Log::error('打赏失败', ['message' => $exception->getMessage()]);
                return $this->apiError('打赏失败');
            }
        }
    }

    /**
     * 检测打赏者
     * @param $data
     * @return void
     * @throws CustomException
     */
    private function checkRewardPerson($data): void
    {
        $userId = auth('user')->id();
        if ($data['target_user_id'] == $userId) {
            throw new CustomException(['message' => '不能给自己打赏']);
        }
        if ($data['reward_money'] <= 0) {
            throw new CustomException(['message' => '打赏金额不允许']);
        }
        $user = DB::table('users')->where('id', $userId)->select([
            'is_lock', 'is_balance_freeze', 'account_balance'
        ])->first();
        $user = (array)$user;
        if ($user['account_balance'] < $data['reward_money']) {
            throw new CustomException(['message' => '余额不足,打赏失败']);
        }
        if ($user['is_balance_freeze'] == 1) {
            throw new CustomException(['message' => '资金被冻结,打赏失败']);
        }
        if ($user['is_lock'] == 1) {
            throw new CustomException(['message' => '账号锁定,打赏失败']);
        }
        if (!DB::table('user_rewards')->where('user_id', $userId)->exists()) {
            // 判断是否在平台充值
            if (!$this->checkRecharge($userId)) {
                throw new CustomException(['message' => '打赏者未充值']);
            }
        }
    }

    private function checkRecharge($user_id): bool
    {
        try {
            if (DB::table('user_plat_recharge_dates')->where('user_id', $user_id)->exists()) {
                return true;
            }
            $userPlats = UserPlatform::query()
                ->where('user_id', $user_id)
                ->where('status', 1)
                ->whereHas('plats', function ($query) {
                    $query->where('status', 1)->select(['id', 'name']);
                })
                ->with([
                    'plats' => function ($query) {
                        $query->select(['id', 'name']);
                    }
                ])
                ->latest()->get();
            if ($userPlats->isEmpty()) {
                throw new CustomException(['message' => '用户未绑定平台']);
            }
            $userPlats = $userPlats->toArray();
            foreach ($userPlats as $k => $v) {
                $res = (new PlatformService())->checkRechargeDate($v['plat_id'], $v['plat_user_account'], $user_id);
                if ($res) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 检测被打赏者
     * @param $target_user_id
     * @return void
     * @throws CustomException
     */
    private function checkBeRewardPerson($target_user_id): void
    {
        $user = DB::table('users')->where('id', $target_user_id)->select([
            'status', 'is_lock', 'is_balance_freeze'
        ])->first();
        if (!$user) {
            throw new CustomException(['message' => '被打赏者不存在']);
        }
        $user = (array)$user;
        if ($user['status'] == 2) {
            throw new CustomException(['message' => '被打赏者账号异常']);
        }
        if ($user['is_lock'] == 1) {
            throw new CustomException(['message' => '被打赏者账号锁定']);
        }
        if ($user['is_balance_freeze'] == 1) {
            throw new CustomException(['message' => '被打赏者资金冻结，打赏失败']);
        }
        $apply = DB::table('income_applies')->where('user_id', $target_user_id)->select(['status'])->first();
        $apply = (array)$apply;
        if (!$apply || $apply['status'] != 1) {
            throw new CustomException(['message' => '被打赏者未开通打赏功能']);
        }
        if (!DB::table('user_rewards')->where('be_user_id', $target_user_id)->exists()) {
            // 判断是否在平台充值
            if (!$this->checkRecharge($target_user_id)) {
                throw new CustomException(['message' => '被打赏者未充值']);
            }
        }
    }

    /**
     * 打赏列表
     * @param array $data
     * @return JsonResponse
     */
    public function reward_list(array $data): JsonResponse
    {
        $userId = auth('user')->id();
        // 总收益
        $total_money = DB::table('user_rewards')->where('be_user_id', $userId)->where('status', 1)->sum('reward_money');
        // 今日收益
        $today_money = DB::table('user_rewards')->where('be_user_id', $userId)->where('status', 1)->whereDate('created_at', date('Y-m-d'))->sum('reward_money');
        // 昨日收益
        $yesterday_money = DB::table('user_rewards')->where('be_user_id', $userId)->where('status', 1)->whereDate('created_at', Carbon::yesterday()->format('Y-m-d'))->sum('reward_money');
        $res = UserReward::query()
            ->where('be_user_id', $userId)
            ->where('status', 1)
            ->latest()
            ->simplePaginate($data['limit'])->toArray();
        return $this->apiSuccess('', [
            'total_money'     => $total_money,
            'today_money'     => $today_money,
            'yesterday_money' => $yesterday_money,
            'list'            => $res['data'],
        ]);
    }

    /**
     * 发帖收益列表
     * @param array $data
     * @return JsonResponse
     */
    public function posts_list(array $data): JsonResponse
    {
        $userId = auth('user')->id();
        // 申请状态
        $status = DB::table('income_applies')->where('user_id', $userId)->value('status');
        // 总收益
        $total_money = DB::table('zan_read_money')->where('user_id', $userId)->sum('money');
        // 今日收益
        $today_money = DB::table('zan_read_money')->where('user_id', $userId)->whereDate('created_at', date('Y-m-d'))->sum('money');
        // 昨日收益
        $yesterday_money = DB::table('zan_read_money')->where('user_id', $userId)->whereDate('created_at', Carbon::yesterday()->format('Y-m-d'))->sum('money');
        $res = ZanReadMoney::query()
            ->where('user_id', $userId)
            ->latest()
            ->simplePaginate($data['limit'])->toArray();
        return $this->apiSuccess('', [
            'total_money'     => $total_money,
            'today_money'     => $today_money,
            'yesterday_money' => $yesterday_money,
            'status'          => $status ?: -2,
            'list'            => $res['data'],
        ]);
    }
}
