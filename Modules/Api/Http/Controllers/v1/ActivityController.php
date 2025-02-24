<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\ActivityRequest;
use Modules\Api\Services\activity\ActivityService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class ActivityController extends BaseApiController
{

    /**
     * 转发
     * @return JsonResponse
     */
    public function forward(): JsonResponse
    {
//        dd(md5('CheckUserExist#fyh2013#1d6d1516969045afd34acc033f619a15'));
        return (new ActivityService())->forward();
    }

    /**
     * 补填邀请码
     * @param ActivityRequest $request
     * @return JsonResponse
     * @throws CustomException
     * @throws ApiException
     */
    public function filling(ActivityRequest $request): JsonResponse
    {
        $request->validate('filling');

        return (new ActivityService())->filling($request->only(['invite_code']));
    }

    /**
     * 活动中心列表
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        return (new ActivityService())->list();
    }

    /**
     * 活动-领取
     * @param ActivityRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function receive(ActivityRequest $request): JsonResponse
    {
        $request->validate('receive');

        return (new ActivityService())->receive($request->only(['type']));
    }

    /**
     * 五福进度
     * @return JsonResponse
     */
    public function five_schedule()
    {
        return (new ActivityService())->five_schedule();
    }

    /**
     * 五福红包领取
     * @return JsonResponse
     * @throws CustomException
     */
    public function five_receive(Request $request)
    {
        return (new ActivityService())->five_receive($request->input('fiveId'));
    }

}
