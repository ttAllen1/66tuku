<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\MysteryRequest;
use Modules\Api\Services\mystery\MysteryService;

class MysteryController extends BaseApiController
{
    /**
     * 最新一期玄机锦囊
     * @param MysteryRequest $request
     * @return JsonResponse
     */
    public function latest(MysteryRequest $request): JsonResponse
    {
        $request->validate('latest');

        return (new MysteryService())->latest($request->only(['year', 'lotteryType']));
    }

    /**
     * 玄机锦囊历史数据
     * @param MysteryRequest $request
     * @return JsonResponse
     */
    public function history(MysteryRequest $request): JsonResponse
    {
        $request->validate('history');

        return (new MysteryService())->history($request->only(['year', 'lotteryType']));
    }
}
