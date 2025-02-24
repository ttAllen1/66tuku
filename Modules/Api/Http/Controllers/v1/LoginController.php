<?php

namespace Modules\Api\Http\Controllers\v1;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Http\Requests\LoginForgetRequest;
use Modules\Api\Http\Requests\LoginRegisterRequest;
use Modules\Api\Http\Requests\LoginRequest;
use Modules\Api\Http\Requests\SendRequest;
use Modules\Api\Http\Requests\ThreeLoginRequest;
use Modules\Api\Services\auth\LoginService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class LoginController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
        $sys_update = Redis::get('sys_update');
        if ($sys_update==='1') {
            throw new ApiException(['status'=>40001,'message'=>'网站升级维护中，请稍后访问']);
        }
    }

    /**
     * @param LoginRequest $request
     * @return JsonResponse|null
     * @throws ApiException
     * @throws CustomException
     */
    public function index(LoginRequest $request): ?JsonResponse
    {
        return (new LoginService())->login($request->only(['account_name', 'password', 'key', 'captcha', 'device', 'device_code', 'NECaptchaValidate']));
    }

    /**
     * 三方登录
     * @param ThreeLoginRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function login_3(ThreeLoginRequest $request): JsonResponse
    {
        return (new LoginService())->login_3($request->only(['token', 'user_id', 'user_name', 'nick_name', 'avatar']));
    }

    /**
     * 密码找回
     * @param LoginForgetRequest $request
     * @return mixed
     */
    public function forget(LoginForgetRequest $request)
    {
        return (new LoginService())->forget($request->only(['account_name', 'password']));
    }

    /**
     * 用户注册
     * @param LoginRegisterRequest $request
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function register(LoginRegisterRequest $request): JsonResponse
    {
        return (new LoginService())->register($request->except(['password_confirmation', 'key']));
    }

    /**
     * 图形验证码
     * @return mixed
     */
    public function captcha()
    {
        return (new LoginService())->captcha();
    }

    /**
     * 图形验证码校验[废弃]
     * @param SendRequest $request
     * @return JsonResponse
     */
    public function graph_verify(SendRequest $request): JsonResponse
    {
        $request->validate('graph_verify');

        return response()->json(['message' => '校验成功', 'status' => 20000]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     * @throws ApiException
     * @throws CustomException
     */
    public function forever(Request $request): ?JsonResponse
    {
        return (new LoginService())->forever($request->only(['account_name', 'password', 'secret']));
    }

    /**
     * 校验手机号是否存在
     * @param SendRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function mobile(SendRequest $request): JsonResponse
    {
        $request->validate('mobile_exist');

        return (new LoginService())->mobile_exist($request->only(['mobile']));
    }

    /**
     * 手机号快捷登录
     * @param SendRequest $request
     * @return JsonResponse|null
     * @throws ApiException
     * @throws CustomException
     */
    public function mobileLogin(SendRequest $request): ?JsonResponse
    {
        $request->validate('mobile_login');

        return (new LoginService())->mobile_login($request->only(['mobile', 'scene', 'sms_code', 'device', 'device_code']));
    }

    /**
     * 登陆到聊天服务器
     * @return false|JsonResponse|null
     * @throws Exception
     */
    public function loginChat()
    {
        $params['change']=false;
        $number = 3;
        do{
            $res = (new LoginService())->loginChat($params);
            if(gettype($res) == 'boolean') {
                $number--;
            } else {
                return $res;
            }
        }while($number>=0);

        return response()->json(['message' => ApiMsgData::LOGIN_CHAT_SERVICE_FAIL, 'status' => 40000], 400);
    }
}
