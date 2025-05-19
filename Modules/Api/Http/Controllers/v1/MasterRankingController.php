<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\MasterRankingRequest;
use Modules\Api\Services\master\MasterRankingService;
use Modules\Common\Exceptions\CustomException;

class MasterRankingController extends BaseApiController
{

    /**
     * 配置信息
     * @return JsonResponse
     */
    public function configs(): JsonResponse
    {
        return (new MasterRankingService())->configs();
    }

    /**
     * @param MasterRankingRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(MasterRankingRequest $request): JsonResponse
    {
        $request->validate('create');
        return (new MasterRankingService())->create($request->all());
    }

    public function list(MasterRankingRequest $request): JsonResponse
    {
        $request->validate('list');

        return (new MasterRankingService())->get_page_list($request->only([
            'lotteryType', 'page', 'config_id', 'issue', 'sort', 'is_fee', 'is_master', 'filter', 'min_accuracy',
            'min_issue'
        ]));
    }

}
