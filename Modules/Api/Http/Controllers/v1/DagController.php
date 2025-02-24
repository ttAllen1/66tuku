<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\KyLoginRequest;
use Modules\Api\Services\game\DagService;

class DagController extends BaseApiController
{

    /**
     * 获取跳转链接
     * @param KyLoginRequest $request
     * @return JsonResponse|null
     */
    public function login(KyLoginRequest $request)
    {
        return (new DagService())->login($request->only(['gameId']));
    }

}
