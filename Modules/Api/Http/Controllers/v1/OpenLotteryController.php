<?php

namespace Modules\Api\Http\Controllers\v1;

use Modules\Api\Http\Requests\OpenLotteryRequest;
use Modules\Api\Services\lottery\OpenLotteryService;

class OpenLotteryController extends BaseApiController
{
    public function open(OpenLotteryRequest $request)
    {
        $request->validate('open');

        return (new OpenLotteryService())->real_open($request->only(['client_id', 'lotteryType']));
    }

}
