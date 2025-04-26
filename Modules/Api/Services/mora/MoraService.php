<?php

namespace Modules\Api\Services\mora;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\Mora;
use Modules\Api\Models\User;
use Modules\Api\Models\UserRead;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\follow\FollowService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class MoraService extends BaseApiService
{

    /**
     * 列表
     * @param $params
     * @return JsonResponse
     */
    public function list($params): JsonResponse
    {
        $type = $params['type'] ?? 0;
        $userId = auth('user')->id();
        $res = Mora::query()
            ->when($type == 0, function ($query) use ($userId) {
                $query->where('user_id', $userId)->orWhere('join_user_id', $userId);
            })
            ->with([
                'user'        => function ($query) {
                    $query->select(['id', 'account_name', 'avatar']);
                }, 'joinUser' => function ($query) {
                    $query->select(['id', 'account_name', 'avatar']);
                }
            ])
            ->orderByDesc('created_at')
            ->simplePaginate(20);

        return $this->apiSuccess('', $res->items());

    }

    /**
     * 论坛列表
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function square($params): JsonResponse
    {
        $userId = null;
        if (!empty($params['account_name'])) {
            $user = User::query()
                ->where('account_name', $params['account_name'])
                ->first();
            if (!$user) {
                throw new CustomException(['message' => '用户不存在']);
            }
            $userId = $user->id;
        }
        $list = Mora::query()
            ->when(!empty($userId), function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'user' => function ($query) {
                    $query->select(['id', 'account_name', 'avatar']);
                }
            ])
            ->select(['id', 'user_id', 'money', 'created_at'])
            ->orderBy('money')
            ->where('result', -1)
            ->simplePaginate(20);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list->items());
    }

    /**
     * 创建
     * @param array $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(array $params): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = auth('user')->id();

            // 使用原子操作一次性查询并检查余额
            $affected = DB::table('users')
                ->where('id', $userId)
                ->where('account_balance', '>=', $params['money'])
                ->decrement('account_balance', $params['money']);

            if ($affected === 0) {
                throw new CustomException(['message' => '余额不足,发布失败']);
            }

            // 创建记录
            Mora::query()->create([
                'user_id'     => $userId,
                'jia_content' => $params['jia_content'],
                'money'       => $params['money']
            ]);

            // 增加金币记录
            (new ActivityService())->modifyAccount($userId, 'publish_mora', $params['money']);

            DB::commit();

            return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);

        } catch (\Exception $e) {
            DB::rollBack();

            // 根据异常类型返回更具体的错误信息
            $message = $e instanceof CustomException
                ? $e->getMessage()
                : '系统错误，请稍后重试';

            throw new CustomException(['message' => $message]);
        }
    }

    public function join(array $params): JsonResponse
    {
        DB::beginTransaction();

        try {
            $userId = auth('user')->id();

            // 1. 获取并锁定竞猜记录
            $mora = Mora::lockForUpdate()
                ->where('id', $params['id'])
                ->where('result', -1)
                ->where('user_id', '!=', $userId)
                ->firstOrFail();

            // 2. 验证用户余额（原子操作）
            $affected = DB::table('users')
                ->where('id', $userId)
                ->where('account_balance', '>=', $mora->money)
                ->decrement('account_balance', $mora->money);

            if ($affected === 0) {
                throw new CustomException(['message' => '余额不足']);
            }

            // 3. 更新竞猜记录
            $mora->fill([
                'join_user_id' => $userId,
                'yi_content'   => $params['yi_content']
            ]);

            // 4. 判断胜负结果
            $result = $this->determineResult($mora->jia_content, $params['yi_content']);
            $mora->result = $result;

            // 5. 处理资金变动
            $this->handleFunds($mora, $userId, $result);

            // 6. 生成结果消息
            $msg = $this->generateResultMessage($mora->jia_content, $params['yi_content'], $result, $mora->money);

            $mora->save();
            DB::commit();

            return $this->apiSuccess($msg);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            throw new CustomException(['message' => '竞猜不存在或已被参与']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('参与竞猜失败', [
                'user_id' => $userId ?? null,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);
            throw new CustomException(['message' => $e instanceof CustomException ? $e->getMessage() : '系统错误，请稍后重试']);
        }
    }

    /**
     * 判断猜拳结果
     */
    private function determineResult(string $jiaContent, string $yiContent): int
    {
        if ($jiaContent === $yiContent) {
            return 0; // 平局
        }

        $winConditions = [
            '石头' => '剪刀',
            '剪刀' => '布',
            '布'   => '石头'
        ];

        return $winConditions[$jiaContent] === $yiContent ? 1 : 2; // 1:甲方赢 2:乙方赢
    }

    /**
     * 处理资金变动
     */
    private function handleFunds(Mora $mora, int $userId, int $result): void
    {
        $feeRate = AuthActivityConfig::val('mora_fee') / 100 ?? 0;
        $serviceFee = $mora->money * $feeRate;
        $actualAmount = $mora->money - $serviceFee;

        switch ($result) {
            case 0: // 平局
                DB::table('users')->where('id', $mora->user_id)->increment('account_balance', $mora->money);
                (new ActivityService())->modifyAccount($mora->user_id, 'publish_mora_draw', $mora->money);
                break;

            case 1: // 甲方赢
                DB::table('users')
                    ->where('id', $mora->user_id)
                    ->increment('account_balance', $mora->money + $actualAmount);

                (new ActivityService())->modifyAccount($mora->user_id, 'publish_mora_win', $mora->money + $actualAmount);
                (new ActivityService())->modifyAccount($userId, 'publish_mora_loss', $mora->money);
                break;

            case 2: // 乙方赢
                DB::table('users')
                    ->where('id', $userId)
                    ->increment('account_balance', $actualAmount);

                (new ActivityService())->modifyAccount($userId, 'publish_mora_win', $actualAmount);
                break;
        }
    }

    /**
     * 生成结果消息
     */
    private function generateResultMessage(string $jiaContent, string $yiContent, int $result, float $amount): string
    {
        $messages = [
            0 => "平局：甲方（{$jiaContent}）；我方（{$yiContent}）",
            1 => "甲方获胜：甲方（{$jiaContent}）；我方（{$yiContent}）",
            2 => "我方获胜：甲方（{$jiaContent}）；我方（{$yiContent}）；奖金:（{$amount}）"
        ];

        return $messages[$result] ?? '未知结果';
    }

    /**
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail($params): JsonResponse
    {
        try {
            $discuss = Discuss::query()
                ->where('status', 1)
                ->with('comments', 'images')
                ->findOrFail($params['id']);
            $discuss['content'] = $this->custom_strip_tags(html_entity_decode($discuss['content']));
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '数据不存在']);
        }
        if ($discuss['views'] == 0) {
            $discuss->increment('views', $this->getFirstViews());
        } else {
            $discuss->increment('views', $this->getSecondViews());
        }

        $userId = auth('user')->id();
        if ($userId) {
            $discuss['follow'] = (bool)$discuss->follow()->where('user_id', $userId)->value('id');
            // 增加真实浏览量
            UserRead::query()->insertOrIgnore([
                'user_id'     => $userId,
                'year'        => $discuss['year'],
                'lotteryType' => $discuss['lotteryType'],
                'issue'       => $discuss['issue'],
                'type'        => 1,
                'target_id'   => $discuss['id'],
                'created_at'  => date('Y-m-d H:i:s')
            ]);
        }

        // 详情广告
//        $adList = (new AdService())->getAdListByPoi([2]);
//        $discuss['adList'] = $adList;

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $discuss->toArray());
    }

    /**
     * 全部主题点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        $discussBuilder = Discuss::query()->where('id', $params['id']);

        return (new FollowService())->follow($discussBuilder);
    }

    /**
     * 论坛上一期内容
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function previous($params): JsonResponse
    {
        $lotteryType = $params['lotteryType'];
        $userId = auth('user')->id();

        $discuss = Discuss::query()->where('user_id', $userId)
            ->where('lotteryType', $lotteryType)
            ->where('status', 1)
            ->select(['id', 'title', 'content', 'word_color', 'created_at'])
            ->latest()
            ->with(['images'])
            ->first();
        if (!$discuss) {
            throw new CustomException(['message' => '数据不存在']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $discuss->toArray());
    }
}
