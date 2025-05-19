<?php

namespace Modules\Api\Services\master;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\MasterRanking;
use Modules\Api\Models\MasterRankingConfig;
use Modules\Api\Models\UserMasterRanking;
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
                'error'  => $e->getMessage(),
                'sql'    => $e->getSql(),
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
        if ($sortType == 2) {
            $issue = 0; // 只要是最新发表，issue 就是 0
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
                $orderBy = $issue == 0 ? 'lot_lat.total_issues DESC' : 'total_issues DESC';
        }
        if ($issue == 0) {
            // 1️⃣ 子查询：每个 user_id 在当前过滤条件下的最新一期 issue
            $subLatest = MasterRanking::query()
                ->selectRaw('user_id, MAX(issue) AS latest_issue, COUNT(*) AS total_issues')
                ->where('lotteryType', $params['lotteryType'])
                ->where('config_id', $params['config_id'])
                ->where('year', $year)
                // 只对“付费/免费”的过滤也要应用到子查询
                ->when($isFee != -1, function ($q) use ($isFee) {
                    if ($isFee === 0) {
                        $q->where('type', 0);
                    } else {
                        $q->where('type', '>', 0);
                    }
                })
                // 如果有 accuracy 和 total_issues 的初级过滤，也可加（可选）
                ->when($filter == 1, function ($q) use ($minAcc, $minIssue) {
                    $q->havingRaw('accuracy >= ?', [$minAcc])
                        ->havingRaw('COUNT(*) >= ?', [$minIssue]);
                })
                ->groupBy('user_id');

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
            lot_lat.total_issues,
            lot_mr.id,
            lot_mr.accuracy,
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
        } else {
            $stats = $this->fetchStats($params, $range, $perPage, $page, $orderBy, $year);
            if ($stats->isEmpty()) {
                return $this->apiSuccess();
            }
            $detailsKeyed = $this->fetchDetails($stats);
            $result = $detailsKeyed->toArray();
        }

        return $this->apiSuccess('', $result);
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
                DB::raw('COUNT(*)                    AS total_issues'),
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

        // 初级过滤：accuracy & total_issues
        if ($filter === 1) {
            $query->havingRaw('accuracy >= ?', [$minAcc])
                ->havingRaw('total_issues >= ?', [$minIssue]);
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
