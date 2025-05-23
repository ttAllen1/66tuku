<?php

namespace Modules\Admin\Services\master;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\MasterRanking;
use Modules\Common\Services\BaseService;

class MasterRankingService extends BaseApiService
{
    /**
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $userId = [];
        if (!empty($data['nickname'])) {
            $userId = DB::table('users')
                ->where('account_name', 'like', '%' . $data['nickname'] . '%')
                ->orWhere('nickname', 'like', '%' . $data['nickname'] . '%')
                ->get()
                ->pluck('id')
                ->toArray();
            if (!$userId) {
                return $this->apiSuccess();
            }
        }
        $list = MasterRanking::query()
            ->when($userId, function ($query) use ($userId) {
                $query->whereIn('user_id', $userId);
            })
            ->with([
                'config' => function ($query) {
                    $query->select('id', 'name');
                },
                'user' => function ($query) {
                    $query->select('id', 'nickname', 'avatar');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'] ?? 10)
            ->toArray();

        return $this->apiSuccess('', [
            'list' => $list['data'],
            'total' => $list['total'],
        ]);
    }

    /**
     * @param array $data
     * @return JsonResponse|null
     */
    public function update(array $data): ?JsonResponse
    {
        $id = $data['id'];
        unset($data['id']);
        $data['content'] = strip_tags($data['content']);
        return (new BaseService())->commonUpdate(MasterRanking::query(), $id, $data);
    }
}
