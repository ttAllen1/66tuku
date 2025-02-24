<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\HumorousRequest;
use Modules\Api\Services\humorous\HumorousService;
use Modules\Common\Exceptions\CustomException;

class HumorousController extends BaseApiController
{
    /**
     * 期数列表
     * @param HumorousRequest $request
     * @return JsonResponse
     */
    public function guess(HumorousRequest $request): JsonResponse
    {
        $request->validate('guess');

        return (new HumorousService())->guess($request->only(['year', 'lotteryType']));
    }

    /**
     * 竞猜详情
     * @param HumorousRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail(HumorousRequest $request): JsonResponse
    {
        $request->validate('detail');

        return (new HumorousService())->detail($request->only(['id']));
    }

    /**
     * 竞猜点赞（取消）
     * @param HumorousRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(HumorousRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new HumorousService())->follow($request->only(['id']));
    }

    /**
     * 竞猜收藏（取消）
     * @param HumorousRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect(HumorousRequest $request): JsonResponse
    {
        $request->validate('collect');

        return (new HumorousService())->collect($request->only(['id']));
    }

    /**
     * 竞猜投票
     * @param HumorousRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function vote(HumorousRequest $request): JsonResponse
    {
        $request->validate('vote');

        return (new HumorousService())->votes($request->only(['id', 'vote_zodiac']));
    }
}
