<?php
/**
 * 会员手机号黑名单管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\UserBlacklistMobile;

class UserMobileBlackListService extends BaseApiService
{
    /**
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $mobile = $data['mobile'] ?? '';
        $list = UserBlacklistMobile::query()
            ->when($mobile, function($query) use ($mobile) {
                $query->where('mobile', 'like', '%'.$mobile.'%');
            })
            ->latest()
            ->paginate($data['limit'])
            ->toArray();

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total']
        ]);
    }

    /**
     * @param $id
     * @return JsonResponse|null
     */
    public function delete($id): ?JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }

        return $this->commonDestroy(UserBlacklistMobile::query(), $id);
    }
}
