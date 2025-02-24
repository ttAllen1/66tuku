<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\StatisticsRequest;
use Modules\Api\Services\statistics\StatisticsService;
use Modules\Common\Exceptions\CustomException;

class StatisticsController extends BaseApiController
{
    /**
     * 统计设备下载量
     * @param StatisticsRequest $request
     * @return JsonResponse|null
     */
    public function index(StatisticsRequest $request): ?JsonResponse
    {
        return (new StatisticsService())->statistics($request->only(['device', 'device_code']));
    }

    /**
     * 记录失效的域名
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function domain(Request $request): JsonResponse
    {
        return (new StatisticsService())->domain($request->only(['device', 'domain']));
    }
}
