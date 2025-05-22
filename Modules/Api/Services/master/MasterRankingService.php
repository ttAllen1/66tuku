<?php

namespace Modules\Api\Services\master;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\MasterRanking;
use Modules\Api\Models\MasterRankingConfig;
use Modules\Api\Models\User;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\CustomException;

class MasterRankingService extends BaseApiService
{
    /**
     * 配置信息
     * @return JsonResponse
     */
    public function configs(): JsonResponse
    {
        $list = MasterRankingConfig::query()
            ->where('status', 1)
            ->where('pid', 0)
            ->orderBy('sort', 'asc')
            ->select('id', 'pid', 'name', 'sort', 'mark')
            ->with([
                'children' => function ($query) {
                    $query->where('status', 1)
                        ->orderBy('sort', 'asc')
                        ->select('id', 'pid', 'name', 'sort', 'mark');
                }
            ])
            ->get()
            ->toArray();
        return $this->apiSuccess('', $list);
    }

    /**
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function create($params): JsonResponse
    {
//        dd($params);
        // 检测当前时间能否创建 20:30开始
        $now = date('H:i');
        $lotteryType = $params['lotteryType'];
        if (in_array($lotteryType, [1, 2, 6, 7])) {
            if ($now >= '20:30') {
                throw new CustomException(['message' => '数据不合法']);
            }
        } else {
            if ($now >= '21:30') {
                throw new CustomException(['message' => '当前时间不可创建']);
            }
        }
//        dd($params);
        // 检测当前配置id可否创建
        $configInfo = (array)DB::table('master_ranking_configs')
            ->where('id', $params['config_id'])
            ->first();
        if (!$configInfo) {
            throw new CustomException(['message' => '创建信息不正确']);
        }
        if ($configInfo['status'] != 1 || $configInfo['pid'] == 0) {
            throw new CustomException(['message' => '当前配置不可创建']);
        }
        // 检测数据是否合法
        if (!MasterRankingConfig::checkInputData($params['mark'], $params['content'])) {
            throw new CustomException(['message' => '数据不合法']);
        }
        // 检测数据是否存在
        $userId = auth('user')->id();
        $issue = $this->getNextIssue($lotteryType);
        $year = date('Y');
        $fee = $params['fee'] ?? 0;
        try {
            DB::beginTransaction();
            $now = date('Y-m-d H:i:s');

            $existsData = DB::table('master_rankings')
                ->where('user_id', $userId)
                ->where('lotteryType', $params['lotteryType'])
                ->where('config_id', $params['config_id'])
                ->orderByDesc('created_at')
                ->first();
            $data = [
                'lotteryType' => $params['lotteryType'],
                'content'     => $params['content'],
                'year'        => $year,
                'issue'       => $issue,
                'user_id'     => $userId,
                'config_id'   => $params['config_id'],
                'type'        => $params['type'],
                'fee'         => $fee,
                'created_at'  => $now,
            ];
            if ($existsData) {
                $data['max_right'] = $existsData->max_right;
                $data['max_wrong'] = $existsData->max_wrong;
                $data['current_right'] = $existsData->current_right;
                $data['current_wrong'] = $existsData->current_wrong;
                $data['score'] = $existsData->score;
                $data['five_res'] = $existsData->five_res;
                $data['ten_res'] = $existsData->ten_res;
                $data['twenty_res'] = $existsData->twenty_res;
                $data['accuracy'] = $existsData->accuracy;
                $data['total_right'] = $existsData->total_right;
                $data['total_wrong'] = $existsData->total_wrong;
                $data['total_count'] = $existsData->total_count + 1;

                $recentlyData = explode(',', $existsData->recently_data);
                if (count($recentlyData) > 20) {
                    array_shift($recentlyData);
                }
                $data['recently_data'] = implode(',', $recentlyData) . ', 0';
            }
            DB::table('master_rankings')->insert($data);

//            if (!Redis::exists('user_master_rankings_' . $userId)) {
//                if (!DB::table('user_master_rankings')->where([
//                    'user_id'     => $userId,
//                    'lotteryType' => $params['lotteryType'],
//                    'config_id'   => $params['config_id'],
//                ])->exists()) {
//                    DB::table('user_master_rankings')->insert([
//                        'user_id'     => $userId,
//                        'lotteryType' => $params['lotteryType'],
//                        'config_id'   => $params['config_id'],
//                        'created_at'  => $now,
//                    ]);
//                }
//                Redis::set('user_master_rankings_' . $userId, 1);
//            }
            DB::commit();


            return $this->apiSuccess('创建成功');
        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                // 1062 是 MySQL 的唯一键冲突错误码
                return $this->apiSuccess('不能重复创建');
            }

            // 如果是别的错误，继续抛出
            Log::error('新高手榜创建失败', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'params' => $e->getBindings(),
            ]);
            throw new CustomException(['message' => '创建失败']);
        }

    }

    /**
     * 高手榜列表
     * @param array $params
     * @return JsonResponse
     */
    public function get_page_list(array $params): JsonResponse
    {
        $lotteryType = $params['lotteryType'] ?? 0;
        $configId = $params['config_id'] ?? 0;
        $range = $issue = $params['issue'] ?? 0; // filter=1时，必须为0。期数范围：0全部；5：近5期；10:近10期；20:近20期
        $sortType = $params['sort'] ?? 0;   // 排序：0期数最多；1正确率；2最新发表；3连对最多；4连错最多
        if ($sortType == 2 || $sortType == 3 || $sortType == 4 || $sortType == 0) {
            $issue = 0; // 只要不是正确率，issue 就是 0
        }
        $isFee = $params['is_fee'] ?? -1; // 权限：-1全部；0免费；1付费
        $is_master = $params['is_master'] ?? 0; // 是否上榜：0未上榜；1上榜

        $filter = $params['filter'] ?? 0; // 高级筛选：0关闭；1开启
        $minAcc = $params['min_accuracy'] ?? 0; // filter=1:最低正确率
        $minIssue = $params['min_issue'] ?? 0; // filter=1:最低总期数

        $perPage = 10;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $perPage;

        if ($filter == 1) {
            $issue = 0;
        } else {
            $minAcc = 0;
            $minIssue = 0;
        }
        $year = date('Y');
        // 构造排序字段
        switch ($sortType) {
            case 1:
                $orderBy = 'accuracy DESC';
                break;
            case 2:
                $orderBy = 'created_at DESC';
                break;
            case 3:
                $orderBy = 'max_right DESC';
                break;
            case 4:
                $orderBy = 'max_wrong DESC';
                break;
            default:
                $orderBy = 'total_count DESC';
        }

        // 1️⃣ 子查询：每个 user_id 在当前过滤条件下的最新一期 issue
        $subLatest = MasterRanking::query()
            ->selectRaw('user_id, MAX(issue) AS latest_issue')
            ->where('lotteryType', $params['lotteryType'])
            ->where('config_id', $params['config_id'])
            ->orderByDesc('year')
            // 只对“付费/免费”的过滤也要应用到子查询
            ->when($isFee != -1, function ($q) use ($isFee) {
                if ($isFee === 0) {
                    $q->where('type', 0);
                } else {
                    $q->where('type', '>', 0);
                }
            })
            // 如果有 accuracy 和 total_count 的初级过滤，也可加（可选）
            ->when($filter == 1, function ($q) use ($minAcc, $minIssue) {
                $q->havingRaw('accuracy >= ?', [$minAcc])
                    ->havingRaw('COUNT(*) >= ?', [$minIssue]);
            })
            ->groupBy('user_id');
        if ($issue > 0) {
            $orderBy = 'five_accuracy DESC';
            if ($issue == 10) {
                $orderBy = 'ten_accuracy DESC';
            } elseif ($issue == 20) {
                $orderBy = 'twenty_accuracy DESC';
            }
        }
        // 2️⃣ 主查询：把最新一期 JOIN 回来，只保留每人最新一期那条记录
        $query = MasterRanking::query()
            ->from('master_rankings as mr')
            ->joinSub($subLatest, 'lat', function ($join) {
                $join->on('mr.user_id', '=', 'lat.user_id')
                    ->on('mr.issue', '=', 'lat.latest_issue');
            })
            // 仍然要加上最初的筛选条件，确保数据一致
            ->where('mr.lotteryType', $params['lotteryType'])
            ->where('mr.config_id', $params['config_id'])
            ->where('mr.year', $year)
            ->when($isFee != -1, function ($q) use ($isFee) {
                if ($isFee == 0) {
                    $q->where('mr.type', 0);
                } else {
                    $q->where('mr.type', '>', 0);
                }
            })
            // 按照你需要的聚合或字段列表来 select
            ->selectRaw(<<<SQL
            lot_mr.user_id,
            lot_mr.total_count,
            lot_mr.id,
            lot_mr.accuracy,
            lot_mr.five_accuracy,
            lot_mr.five_res,
            lot_mr.ten_accuracy,
            lot_mr.ten_res,
            lot_mr.twenty_accuracy,
            lot_mr.twenty_res,
            lot_mr.max_right,
            lot_mr.max_wrong,
            lot_mr.current_right,
            lot_mr.current_wrong,
            lot_mr.fee,
            lot_mr.config_id,
            lot_mr.lotteryType,
            lot_mr.issue,
            lot_mr.type,
            lot_mr.score,
            lot_mr.content,
            lot_mr.praise_num,
            lot_mr.created_at
        SQL
            )
            ->groupBy('mr.user_id')
            // 最后再加排序
            ->orderByRaw($orderBy)
            ->with(['user:id,nickname,avatar']);

        // 3️⃣ 分页返回
        $list = $query->simplePaginate(10);
        $result = $list->toArray()['data'];

        return $this->apiSuccess('', $result);
    }

    /**
     * 获取高手榜详情
     * @param array $params
     * @return JsonResponse
     */
    public function get_page_detail_list(array $params): JsonResponse
    {
        // 处理关注可看
        $userId = auth('user')->id();
        $focusIds = [];
        if ($userId) {
            // 可看我关注的人 $focusIds是我关注的人
            $focusIds = DB::table('user_focusons')->where('user_id', $userId)
                ->pluck('to_userid')->toArray();

        }

        $list = MasterRanking::query()
            ->when(!$userId, function ($query) use ($params) {
                $query->whereIn('type', [0, 2]);
            })
            ->when($userId && $focusIds, function ($query) use ($focusIds) {
                // 登录用户 + 有关注人：可看 免费、付费、我关注的人
                $query->where(function ($query) use ($focusIds) {
                    $query->whereIn('type', [0, 2])
                        ->orWhereIn('user_id', $focusIds);
                });
            })
            ->when($userId && empty($focusIds), function ($query) {
                // 登录用户但没有关注人：只能看 免费 和 付费
                $query->whereIn('type', [0, 2]);
            })
            ->where('lotteryType', $params['lotteryType'])
            ->where('config_id', $params['config_id'])
            ->where('user_id', $params['user_id'])
            ->orderByDesc('created_at')
            ->simplePaginate(10);
        $result = $list->toArray()['data'];
        if (!$result) {
            return $this->apiSuccess('', ['userInfo' => [], 'list' => []]);
        }
        if ($list[0]['type'] == 2) {
            // 判断是否已支付
            if (!$userId || !DB::table('user_master_rankings')->where('user_id', $params['user_id'])
                    ->where('market_id', $list[0]['id'])
                    ->exists()) {
                $nextIssue = $this->getNextIssue($params['lotteryType']);
                if ($nextIssue == $list[0]['issue']) {
                    $list[0]['content'] = '-';
                }
            }
        }

        $userInfo = (array)DB::table('users')->where('id', $params['user_id'])->select([
            'id', 'account_name', 'avatar'
        ])->first();
        $userInfo['score'] = $list[0]['score'];
        $userInfo['accuracy'] = $list[0]['accuracy'];
        $userInfo['max_right'] = $list[0]['max_right'];
        $userInfo['max_wrong'] = $list[0]['max_wrong'];
        $userInfo['current_right'] = $list[0]['current_right'];
        $userInfo['current_wrong'] = $list[0]['current_wrong'];
        return $this->apiSuccess('', [
            'userInfo' => $userInfo,
            'list'     => $result,
        ]);
    }

    /**
     * 购买高手榜
     * @param array $params
     * @return JsonResponse
     */
    public function detail(array $params): JsonResponse
    {
        $userId = auth('user')->id();
        // 是否为“真正”的付费
        $info = (array)DB::table('master_rankings')
            ->where('id', $params['market_id'])
            ->where('type', 2)
            ->where('year', date('Y'))
            ->select(['lotteryType', 'fee', 'issue', 'user_id', 'year', 'content'])
            ->first();
        if (!$info) {
            return $this->apiSuccess('数据不存在');
        }
        if ($info['issue'] != $this->getNextIssue($info['lotteryType'])) {
            return $this->apiSuccess('数据不存在');
        }
        if (DB::table('user_master_rankings')->where('user_id', $userId)->where('market_id', $params['market_id'])->exists()) {
            return $this->apiSuccess('已购买，请勿重复购买');
        }
        // 开始支付
        try{
            DB::beginTransaction();
            $user = User::query()->where('id', $userId)->select(['id', 'account_balance'])->firstOrFail();
            if ($user->account_balance < $info['fee']) {
                DB::rollBack();
                return $this->apiSuccess('余额不足');
            }
            $now = date('Y-m-d H:i:s');
            // 手续费
            $market_fee = AuthActivityConfig::val('market_fee') ?? 0;
            $actual_money = $info['fee'] - ($info['fee'] * $market_fee / 100);
            // 扣除用户余额
            $user->account_balance -= $info['fee'];
            $user->save();
            // 金币记录(付费端)
            $userGolds['user_id'] = $userId;
            $userGolds['type'] = 30;
            $userGolds['gold'] = $info['fee'];
            $userGolds['symbol'] = '-';
            $userGolds['balance'] = $user['account_balance'] - $info['fee'];
            $userGolds['user_market_id'] = $params['market_id'];
            $userGolds['created_at'] = $now;
            DB::table('user_gold_records')->insert($userGolds);
            // 金币记录(收益端)
            $user2 = User::query()->where('id', $info['user_id'])->select(['id', 'account_balance'])->firstOrFail();
            $user2->account_balance += $actual_money;
            $user2->save();
            $userGolds['user_id'] = $info['user_id'];
            $userGolds['type'] = 31;
            $userGolds['gold'] = $actual_money;
            $userGolds['symbol'] = '+';
            $userGolds['balance'] = $user2['account_balance'] + $actual_money;
            $userGolds['user_market_id'] = $params['market_id'];
            $userGolds['created_at'] = $now;
            DB::table('user_gold_records')->insert($userGolds);
            // 购买记录
            DB::table('user_master_rankings')->insert([
                'user_id'     => $userId,
                'market_user_id'     => $info['user_id'],
                'market_id'   => $params['market_id'],
                'lotteryType' => $info['lotteryType'],
                'issue' => $info['issue'],
                'year' => $info['year'],
                'created_at'  => $now,
            ]);
            DB::commit();

            return $this->apiSuccess('购买成功', ['content' => $info['content']]);
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error('高手榜购买失败', [
                'error' => $e->getMessage(),
            ]);
            return $this->apiSuccess('购买失败');
        }

    }

    /**
     * 第一次查询：按 user_id 聚合近 $range 期的数据，返回分页后的统计结果
     */
    private function fetchStats(array $params, int $range, int $perPage, int $page, string $orderBy, string $year)
    {
        $lotteryType = $params['lotteryType'];
        $configId = $params['config_id'];
        $isFee = $params['is_fee'];
        $filter = $params['filter'];
        $minAcc = $params['min_accuracy'];
        $minIssue = $params['min_issue'];

        $offset = ($page - 1) * $perPage;

        $query = DB::table('master_rankings as mr1')
            ->select([
                'mr1.user_id',
                DB::raw('COUNT(*)                    AS total_count'),
                DB::raw('MAX(lot_mr1.issue)               AS latest_issue'),
                DB::raw('MAX(lot_mr1.max_right)               AS max_right'),
                DB::raw('MAX(lot_mr1.max_wrong)               AS max_wrong'),
                DB::raw('MAX(lot_mr1.id)               AS id'),
                DB::raw('MAX(lot_mr1.created_at)               AS created_at'),
            ])
            // 通用条件
            ->where('mr1.lotteryType', $lotteryType)
            ->where('mr1.config_id', $configId)
            ->where('mr1.year', $year)
            ->when($isFee != -1, function ($q) use ($isFee) {
                $q->where('mr1.type', $isFee === 0 ? 0 : '>', 0);
            });

        // 如果取“最近 N 期”，再加“issue >= 阈值”条件
        if ($range > 0) {
            // 子查询：找出该 user_id 的第 N 大 issue
            // OFFSET N-1：0 基下，第 N 大
            $query->whereRaw(<<<SQL
            lot_mr1.issue >= (
                SELECT lr2.issue
                FROM lot_master_rankings AS lr2
                WHERE lr2.user_id     = lot_mr1.user_id
                  AND lr2.lotteryType = ?
                  AND lr2.config_id   = ?
                  AND lr2.year        = ?
                ORDER BY lr2.issue DESC
                LIMIT 1 OFFSET ?
            )
        SQL
                , [$lotteryType, $configId, $year, $range - 1]);
        }

        // 初级过滤：accuracy & total_count
        if ($filter === 1) {
            $query->havingRaw('accuracy >= ?', [$minAcc])
                ->havingRaw('total_count >= ?', [$minIssue]);
        }

        // 分组、排序、分页
        return $query
            ->groupBy('mr1.user_id')
            ->orderByRaw($orderBy)
            ->limit($perPage)
            ->offset($offset)
            ->get();
    }

    private function fetchDetails($stats)
    {
        $ids = $stats->pluck('id')->all();
        // 构造一组 OR 条件
        $details = MasterRanking::with('user:id,nickname,avatar')
            ->whereIn('id', $ids)
            ->get()
            // keyBy id 方便快速合并
            ->keyBy('id');
        return $stats->map(function ($row) use ($details) {
            return $details->get($row->id);
        });
        $detailsOrdered = [];
        foreach ($stats as $row) {
            $key = "{$row->user_id}_{$row->latest_issue}";
            if (isset($detailsKeyed[$key])) {
                $detailsOrdered[$key] = $detailsKeyed[$key];
            }
        }
        return collect($detailsOrdered);
    }
}
