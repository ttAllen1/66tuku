<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\ComplaintsRequest;
use Modules\Api\Services\complaint\ComplaintService;
use Modules\Common\Exceptions\CustomException;

class ComplaintController extends BaseApiController
{
    /**
     * 添加举报
     * @param ComplaintsRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function add(ComplaintsRequest $request): JsonResponse
    {
        return (new ComplaintService())->add($request->only('content', 'images', 'type', 'id'));
    }

    /**
     * 举报列表
     * @return JsonResponse
     */
    public function list()
    {
        return (new ComplaintService())->list();
    }
}
