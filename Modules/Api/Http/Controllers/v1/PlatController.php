<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\PlatRequest;
use Modules\Api\Services\platform\PlatformService;
use Modules\Common\Exceptions\CustomException;

class PlatController extends BaseApiController
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 用户平台列表
     * @return JsonResponse
     * @throws CustomException
     */
    public function user_plat(): JsonResponse
    {

        return (new PlatformService())->user_plat();
    }

    /**
     * 平台列表
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(): JsonResponse
    {

        return (new PlatformService())->list();
    }

    /**
     * 绑定平台
     * @param PlatRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function bind(PlatRequest $request): JsonResponse
    {
        $request->validate('bind');

        return (new PlatformService())->bind($request->only(['plat_id', 'plat_user_name', 'plat_user_account']));
    }

    /**
     * 额度列表
     * @param PlatRequest $request
     * @return JsonResponse
     */
    public function quotas(PlatRequest $request): JsonResponse
    {
        return (new PlatformService())->quotas();
    }

    /**
     * 充值
     * @param PlatRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function recharge(PlatRequest $request): JsonResponse
    {
        $request->validate('recharge');

        return (new PlatformService())->recharge($request->only(['user_plat_id', 'quota']));
    }

    /**
     * 提现页面
     * @return JsonResponse
     * @throws CustomException
     */
    public function withdraw_page(): JsonResponse
    {
        return (new PlatformService())->withdraw_page();
    }

    /**
     * 提现
     * @param PlatRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function withdraw(PlatRequest $request): JsonResponse
    {
        $request->validate('withdraw');

        return (new PlatformService())->withdraw($request->only(['user_plat_id', 'quota', 'fund_password', 'sms_code']));
    }
}
