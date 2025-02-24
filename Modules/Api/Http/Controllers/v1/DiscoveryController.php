<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\DiscoveryRequest;
use Modules\Api\Http\Requests\MysteryRequest;
use Modules\Api\Services\discovery\DiscoveryService;
use Modules\Common\Exceptions\CustomException;

class DiscoveryController extends BaseApiController
{
    /**
     * 发布
     * @param DiscoveryRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(DiscoveryRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new DiscoveryService())->create($request->only(['lotteryType', 'title', 'content', 'type', 'images','videos']));
    }

    /**
     * 列表
     * @param DiscoveryRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(DiscoveryRequest $request): JsonResponse
    {
        $request->validate('list');

        return (new DiscoveryService())->list($request->only(['lotteryType', 'keyword', 'is_rec', 'type']));
    }

    /**
     * 详情
     * @param DiscoveryRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail(DiscoveryRequest $request): JsonResponse
    {
        $request->validate('detail');

        return (new DiscoveryService())->detail($request->only(['id']));
    }

    /**
     * 点赞
     * @param DiscoveryRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(DiscoveryRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new DiscoveryService())->follow($request->only(['id']));
    }

    /**
     * 图片（取消）收藏
     * @param DiscoveryRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect(DiscoveryRequest $request): JsonResponse
    {
        $request->validate('collect');

        return (new DiscoveryService())->collect($request->only(['id']));
    }

    /**
     * 转发
     * @param DiscoveryRequest $request
     * @return JsonResponse
     */
    public function forward(DiscoveryRequest $request): JsonResponse
    {
        $request->validate('forward');

        return (new DiscoveryService())->forward($request->only(['id']));
    }
}
