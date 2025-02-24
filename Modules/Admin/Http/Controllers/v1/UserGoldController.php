<?php
/**
 * 用户金币统计控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\UserGoldService;

class UserGoldController extends BaseApiController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new UserGoldService())->index($request->all());
    }
}
