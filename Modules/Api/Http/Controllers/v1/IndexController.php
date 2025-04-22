<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\IndexRequest;
use Modules\Api\Services\index\IndexService;
use Modules\Common\Exceptions\ApiException;

class IndexController extends BaseApiController
{
    /**
     * 启动图
     * @return JsonResponse
     * @throws ApiException
     */
    public function init_img(): JsonResponse
    {
        return (new IndexService())->init_img();
    }

    /**
     * @param IndexRequest $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function index(IndexRequest $request): JsonResponse
    {
        return (new IndexService())->get_index();
    }

    /**
     * @return JsonResponse
     */
    public function years(): JsonResponse
    {
        return (new IndexService())->years();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function yearsColor(Request $request): JsonResponse
    {
        return (new IndexService())->yearsColor($request->all());
    }

    /**
     * 获取彩种对应黑白年份
     * @return JsonResponse
     */
    public function lotteryYearsColor(): JsonResponse
    {
        return (new IndexService())->lotteryYearsColor();
    }

    /**
     * 获取彩种对应年份
     * @return JsonResponse
     */
    public function lotteryYears(): JsonResponse
    {
        return (new IndexService())->lotteryYears();
    }

    /**
     * @param IndexRequest $request
     * @return JsonResponse
     */
    public function material(IndexRequest $request): JsonResponse
    {
        return (new IndexService())->material($request->all());
    }

    public function guess(IndexRequest $request)
    {
        return (new IndexService())->guess($request->only('lotteryType'));
    }

    public function guess1()
    {
        return (new IndexService())->guess1();
    }
}
