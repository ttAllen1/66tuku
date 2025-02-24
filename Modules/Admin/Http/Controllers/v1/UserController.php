<?php
/**
 * 会员管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonIdRequest;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\UserUpdateRequest;
use Modules\Admin\Services\user\UserService;
use Modules\Common\Exceptions\ApiException;

class UserController extends BaseApiController
{
    /**
     * 列表数据
     * @description
     * @method  GET
     **/
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new UserService())->index($request->all());
    }

    /**
     * 添加
     * @description
     * @method  POST
     **/
    public function store(UserUpdateRequest $request): JsonResponse
    {
        $request->validate($request->input('scene', ''));

        return (new UserService())->store($request->except('scene'));
    }

    /**
     * 编辑页面
     * @param UserUpdateRequest $request
     * @return \Modules\Admin\Services\user\JSON
     */
    public function edit(UserUpdateRequest $request)   // UserUpdateRequest $request
    {
        $request->validate($request->input('scene', ''));
        return (new UserService())->edit($request->get('id'));
    }

    /**
     * 更新
     */
    public function update(UserUpdateRequest $request)
    {
        $scene = $request->input('scene', '');

        $request->validate($scene);
        if ($scene == 'change_user_pw') {
            return (new UserService())->updatePwd($request->get('id'), $request->get('password'));
        } else if ($scene == 'change_user_fund_pw') {
            return (new UserService())->updateFundPwd($request->get('id'), $request->get('fund_password'));
        } else if ($scene == 'user_forbid_speak') {
            return (new UserService())->updateForbidSpeak($request->only(['id', 'room_id', 'is_forbid_speak']));
        }

        return (new UserService())->update($request->get('id'),$request->except('scene'));
    }

    /**
     * 调整状态
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function status(Request $request): JsonResponse
    {
        return (new UserService())->status($request->get('id'),$request->except(['id', 'password', 'fund_password']));
    }

    /**
     * 初始化密码
     * @param CommonIdRequest $request
     * @return JsonResponse
     */
    public function updatePwd(CommonIdRequest $request): JsonResponse
    {
        return (new UserService())->updatePwd($request->get('id'));
    }

    public function user_id_name(UserUpdateRequest $request)
    {
        $request->validate('user_mushin');

        return (new UserService())->user_id_name($request->get('account_name'));
    }

    public function user_id_full_name(UserUpdateRequest $request)
    {
        $request->validate('user_mushin');

        return (new UserService())->user_id_full_name($request->get('account_name'));
    }

    public function id_by_nickname(Request $request)
    {
        return (new UserService())->id_by_nickname($request->get('nickname'));
    }

    /**
     * 管理员登录前端用户
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function login(Request $request): JsonResponse
    {
        return (new UserService())->memberLogin($request->get('userId'));
    }

    /**
     * 修改额度
     * @param Request $request
     * @return JsonResponse
     */
    public function user_quotas(Request $request): JsonResponse
    {
        return (new UserService())->user_quotas($request->input('user_quotas'));
    }
}
