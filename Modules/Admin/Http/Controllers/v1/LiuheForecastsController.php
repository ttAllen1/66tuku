<?php
/**
 * 六合配置
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\liuhe\LiuHeForecastsService;

class LiuheForecastsController extends BaseApiController
{
    /**
     * 列表数据
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return (new LiuHeForecastsService())->index($request->all());
    }

    /**
     * 竞猜设置更新
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        return (new LiuHeForecastsService())->update($request->all());
    }
}
