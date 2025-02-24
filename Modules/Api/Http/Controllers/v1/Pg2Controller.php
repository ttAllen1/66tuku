<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\KyLoginRequest;
use Modules\Api\Services\game\Pg2Service;

class Pg2Controller extends BaseApiController
{

    /**
     * 获取跳转链接
     * @param KyLoginRequest $request
     * @return JsonResponse|null
     */
    public function login(KyLoginRequest $request)
    {
        return (new Pg2Service())->login($request->only(['gameId']));
    }

}
