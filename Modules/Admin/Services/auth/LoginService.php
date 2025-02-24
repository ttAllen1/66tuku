<?php
/**
 * @Name 用户登录服务
 * @Description
 */

namespace Modules\Admin\Services\auth;


use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Services\BaseApiService;
use Modules\Admin\Models\AuthAdmin as AuthAdminModel;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Exceptions\MessageData;
use Modules\Common\Exceptions\StatusData;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LoginService extends BaseApiService
{
    /**
     * 用户登录
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     * @throws ApiException
     */
    public function login(array $data): JsonResponse
    {
        $google_code = $data['google_code'];
        unset($data['google_code']);
        if (Auth::guard('auth_admin')->attempt($data)) {
            $userInfo = AuthAdminModel::query()->where(['username'=>$data['username']])->select('id','username', 'google_secret', 'status')->first();
            $this->checkGoogleQrCode($userInfo['google_secret'], $google_code);
            if($userInfo){
                if ($userInfo['status'] != 1) {
                    $this->apiError('您的账号被禁用');
                }
                $user_info = $userInfo->toArray();
                $user_info['password'] = $data['password'];
                $token = (new TokenService())->setToken($user_info);
                return $this->apiSuccess('登录成功！', (array)$token);
            }
        }

        return $this->apiError('账号或密码错误！');
    }

    /**
     * 验证二维码 及 绑定
     * @param $google_secret
     * @param $google_code
     * @return void
     * @throws CustomException
     */
    private function checkGoogleQrCode($google_secret, $google_code): void
    {
        if(GoogleAuthenticator::CheckCode($google_secret,$google_code)) {
            // 绑定场景：绑定成功，向数据库插入google参数，跳转到登录界面让用户登录
            // 登录认证场景：认证成功，执行认证操作
            return;
        }

        throw new CustomException(['status'=>StatusData::INVALID_ARGUMENT_EXCEPTION,'message'=>MessageData::GOOGLE_CODE_INVALID]);
    }
}
