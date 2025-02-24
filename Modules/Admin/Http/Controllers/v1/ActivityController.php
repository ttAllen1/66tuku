<?php
/**
 * 活动控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\activity\ActivityService;
use Modules\Common\Controllers\BaseController;

class ActivityController extends BaseController
{
    /**
     * 活动配置数据
     * @param Request $request
     * @return JsonResponse
     */
    public function config(Request $request): JsonResponse
    {
        return (new ActivityService())->config($request->all());
    }

    /**
     * 活动配置数据更改
     * @param Request $request
     * @return JsonResponse
     */
    public function config_update(Request $request): JsonResponse
    {
        return (new ActivityService())->config_update($request->except(['id', 'user', 'commentable_id', 'commentable_type']));
    }

    /**
     * 五福活动列表
     * @param Request $request
     * @return JsonResponse
     */
    public function five_index(Request $request): JsonResponse
    {
        return (new ActivityService())->five_index($request->all());
    }
}
