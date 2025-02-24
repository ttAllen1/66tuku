<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\DiagramRequest;
use Modules\Api\Services\diagram\DiagramService;
use Modules\Common\Exceptions\CustomException;

class DiagramController extends BaseApiController
{
    /**
     * 创建图解
     * @param DiagramRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(DiagramRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new DiagramService())->create($request->all());
    }

    /**
     * 图解列表
     * @param DiagramRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(DiagramRequest $request): JsonResponse
    {
        $request->validate('list');

        return (new DiagramService())->list($request->all());
    }

    /**
     * 详情接口
     * @param DiagramRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail(DiagramRequest $request): JsonResponse
    {
        $request->validate('detail');

        return (new DiagramService())->detail($request->only(['id']));
    }

    /**
     * 图解点赞
     * @param DiagramRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(DiagramRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new DiagramService())->follow($request->all());
    }
}
