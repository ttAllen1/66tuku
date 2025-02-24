<?php
/**
 * 用户资金控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\founds\FoundsService;
use Modules\Common\Controllers\BaseController;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class FundsController extends BaseController
{
    /**
     * 平台列表数据
     * @description
     * @method  GET
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function platforms_list(CommonPageRequest $request): JsonResponse
    {
        return (new FoundsService())->platforms_list($request->all());
    }

    public function platforms_update(Request $request): ?JsonResponse
    {
        return (new FoundsService())->platforms_update($request->input('id'), $request->except(['id']));
    }

    public function platforms_store(Request $request): ?JsonResponse
    {
        return (new FoundsService())->platforms_store($request->all());
    }

    public function platforms_delete(Request $request): ?JsonResponse
    {
        return (new FoundsService())->platforms_delete($request->input('id'));
    }


    /**
     * 会员绑定
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function user_platform_list(CommonPageRequest $request): JsonResponse
    {
        return (new FoundsService())->user_platform_list($request->all());
    }

    /**
     * 更新用户平台
     * @param Request $request
     * @return JsonResponse
     */
    public function user_platform_update(Request $request): JsonResponse
    {
        return (new FoundsService())->user_platform_update($request->input('id'), $request->except(['id', 'plats', 'user', 'plat_id', 'user_id', 'created_at', 'updated_at']));
    }

    /**
     * 删除用户平台
     * @param Request $request
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function user_platform_delete(Request $request): ?JsonResponse
    {
        return (new FoundsService())->user_platform_delete($request->input('id'));
    }

    /**
     * 充值列表
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function user_recharge_list(CommonPageRequest $request): JsonResponse
    {
        return (new FoundsService())->user_recharge_list($request->all());
    }

    /**
     * 充值信息更新
     * @param Request $request
     * @return JsonResponse
     */
    public function user_recharge_update(Request $request): JsonResponse
    {
        return (new FoundsService())->user_recharge_update($request->input('id'), $request->except(['id']));
    }

    /**
     * 充值审核
     * @param Request $request
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function user_recharge_update_status(Request $request): ?JsonResponse
    {
        return (new FoundsService())->user_recharge_update_status($request->input('id'), $request->except(['id']));
    }

    public function user_recharge_delete(Request $request): JsonResponse
    {
        return (new FoundsService())->user_recharge_delete($request->input('id'));
    }

    /**
     * 提现列表
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function user_withdraw_list(CommonPageRequest $request): JsonResponse
    {
        return (new FoundsService())->user_withdraw_list($request->all());
    }

    public function user_withdraw_update(Request $request): JsonResponse
    {
        return (new FoundsService())->user_withdraw_update($request->input('id'), $request->only(['id', 'user_plats']));
    }

    /**
     * 提现审核
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function user_withdraw_update_status(Request $request): JsonResponse
    {
        return (new FoundsService())->user_withdraw_update_status($request->input('id'), $request->except(['id']));
    }

    /**
     * 撤回
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function user_withdraw_update_revoke(Request $request): JsonResponse
    {
        return (new FoundsService())->user_withdraw_update_revoke($request->input('id'));
    }

    public function user_withdraw_delete(Request $request): JsonResponse
    {
        return (new FoundsService())->user_withdraw_delete($request->input('id'));
    }

    /**
     * 额度配置
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function quota_list(Request $request): JsonResponse
    {
        return (new FoundsService())->quota_list($request->all());
    }

    /**
     * 额度配置修改
     * @param Request $request
     * @return JsonResponse
     */
    public function quota_update(Request $request): JsonResponse
    {
        return (new FoundsService())->quota_update($request->all());
    }

    /**
     * 投注列表
     * @param CommonPageRequest $request
     * @return JsonResponse|null
     */
    public function bet_list(CommonPageRequest $request): ?JsonResponse
    {
        return (new FoundsService())->bet_list($request->all());
    }

    /**
     * 用户入账操作
     * @param Request $request
     * @return null
     */
    public function bet_account_update(Request $request)
    {
        return (new FoundsService())->bet_account_update($request->all());
    }

    /**
     * 用户一键入账操作
     * @return null
     * @throws ApiException
     */
    public function bet_once_account_update()
    {
        return (new FoundsService())->bet_once_account_update();
    }

    /**
     * 奖项重开操作
     * @param Request $request
     * @return null
     * @throws CustomException
     */
    public function bet_reopen_account_update(Request $request)
    {
        return (new FoundsService())->bet_reopen_account_update($request->only(['lotteryType']));
    }

    /**
     * 修改资金入账方式
     * @param Request $request
     * @return JsonResponse
     */
    public function bet_type_update(Request $request): JsonResponse
    {
        return (new FoundsService())->bet_type_update($request->all());
    }

    /**
     * 收益申请列表
     * @param Request $request
     * @return JsonResponse
     */
    public function user_income_apply_list(Request $request): JsonResponse
    {
        return (new FoundsService())->user_income_apply_list($request->all());
    }

    /**
     * 收益更新
     * @param Request $request
     * @return JsonResponse|null
     */
    public function income_apply_update_status(Request $request): ?JsonResponse
    {
        return (new FoundsService())->income_apply_update_status($request->all());
    }

    /**
     * 收益删除
     * @param Request $request
     * @return JsonResponse|null
     */
    public function income_apply_delete(Request $request): ?JsonResponse
    {
        return (new FoundsService())->income_apply_delete($request->all());
    }


    public function user_quota_list(Request $request): JsonResponse
    {
        return (new FoundsService())->user_quota_list($request->all());
    }

}
