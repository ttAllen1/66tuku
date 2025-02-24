<?php
/**
 * 六合投注配置
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\LiuheBetsRequest;
use Modules\Admin\Services\liuhe\LiuHeForecastBetsService;

class LiuheForecastBetsController extends BaseApiController
{
    /**
     * 创建 ｜ 修改
     * @param LiuheBetsRequest $request
     * @return JsonResponse
     */
    public function store(LiuheBetsRequest $request): JsonResponse
    {
        if ($request->input('id', '') == '') {
            $request->validate('store');
        } else {
            $request->validate('update');
        }

        return (new LiuHeForecastBetsService())->store($request->all());
    }

    /**
     * 投注列表数据
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return (new LiuHeForecastBetsService())->index($request->all());
    }

    /**
     * 竞猜设置更新
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        return (new LiuHeForecastBetsService())->update($request->all());
    }
}
