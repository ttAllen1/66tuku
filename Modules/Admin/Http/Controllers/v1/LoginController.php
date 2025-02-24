<?php
/**
 * 用户登录
 * @Description
 */
namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\LoginRequest;
use Modules\Admin\Services\auth\LoginService;

class LoginController extends BaseApiController
{
    /**
     * 用户登录
     * @description
     * @method  POST
     **/
    public function login(LoginRequest $request): ?\Illuminate\Http\JsonResponse
    {
        return (new LoginService())->login($request->only(['username','password', 'google_code']));
    }
}
