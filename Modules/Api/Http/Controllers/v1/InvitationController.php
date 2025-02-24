<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Services\user\InvitationService;
use Modules\Common\Exceptions\CustomException;

class InvitationController extends BaseApiController
{

    /**
     * 我的推广
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function getInvitation(Request $request): JsonResponse
    {
        return (new InvitationService())->getInvitation($request->only('level'));
    }

    /**
     * 当日推广详情
     * @return JsonResponse
     */
    public function getToDayInvitation(): JsonResponse
    {
        return (new InvitationService())->getToDayInvitation();
    }

    /**
     * 领取推广佣金
     * @return JsonResponse
     * @throws CustomException
     */
    public function getRewards(): JsonResponse
    {
        return (new InvitationService())->getRewards();
    }

    /**
     * 月度统计
     * @return JsonResponse
     */
    public function report(): JsonResponse
    {
        return (new InvitationService())->report();
    }

}
