<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Api\Http\Requests\ForecastRequest;
use Modules\Api\Services\forecast\ForecastService;
use Modules\Common\Exceptions\CustomException;

class ForecastController extends BaseApiController
{
    /**
     * 参与竞猜
     * @return JsonResponse
     */
    public function join(): JsonResponse
    {
        return (new ForecastService())->join();
    }
    /**
     * 参与竞猜【新】
     * @return JsonResponse
     */
    public function newJoin(): JsonResponse
    {
        return (new ForecastService())->newJoin();
    }

    /**
     * 发布竞猜
     * @param ForecastRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(ForecastRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new ForecastService())->create($request->all());
    }

    /**
     * 竞猜列表
     * @param ForecastRequest $request
     * @return JsonResponse
     */
    public function list(ForecastRequest $request): JsonResponse
    {
        $request->validate('list');

        return (new ForecastService())->list($request->only(['pic_detail_id']));
    }

    /**
     * 详情
     * @param ForecastRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail(ForecastRequest $request): JsonResponse
    {
        $request->validate('detail');

        return (new ForecastService())->detail($request->only(['id']));
    }

    /**
     * 点赞
     * @param ForecastRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(ForecastRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new ForecastService())->follow($request->only(['id']));
    }

    /**
     * 排行版
     * @param ForecastRequest $request
     * @return void
     */
    public function ranking(ForecastRequest $request)
    {

    }

    /**
     * 竞猜投注
     * @param ForecastRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function bet(ForecastRequest $request): JsonResponse
    {
        $request->validate('bet');

        return (new ForecastService())->bet($request->all());
    }

    /**
     * 投注列表
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function bet_index(CommonPageRequest $request): JsonResponse
    {
        return (new ForecastService())->bet_index($request->all());
    }

    /**
     * 撤单
     * @param ForecastRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function cancel(ForecastRequest $request): JsonResponse
    {
        $request->validate('cancel');

        return (new ForecastService())->cancel($request->all());
    }
}
