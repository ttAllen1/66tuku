<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Services\ad\AdService;

class AdController extends BaseApiController
{
    public function list(Request $request)
    {
        return (new AdService())->getAdList($request->only(['type', 'lotteryType']));
    }
}
