<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Services\treasure\TreasureService;
use Modules\Common\Exceptions\CustomException;

class TreasureController  extends BaseApiController
{
    /**
     * 寻宝 列表
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(Request $request): JsonResponse
    {
        return (new TreasureService())->list($request->all());
    }
}
