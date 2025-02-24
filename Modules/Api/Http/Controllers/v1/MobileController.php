<?php

namespace Modules\Api\Http\Controllers\v1;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\SendRequest;
use Modules\Api\Services\mobile\MobileService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class MobileController extends BaseApiController
{
    /**
     * 发送验证码
     * @param SendRequest $request
     * @return JsonResponse
     * @throws ApiException
     * @throws CustomException
     */
    public function send(SendRequest $request): JsonResponse
    {
        $request->validate('mobile');

        return (new MobileService())->sendMsg($request->all());
    }

    /**
     * 校验短信验证码
     * @param SendRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function verify(SendRequest $request): JsonResponse
    {
        $request->validate('mobile_verify');

        return (new MobileService())->verify($request->all());
    }

}
