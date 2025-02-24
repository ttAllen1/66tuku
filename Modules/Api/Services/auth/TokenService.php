<?php

namespace Modules\Api\Services\auth;

use Illuminate\Http\JsonResponse;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Exceptions\MessageData;
use Modules\Common\Exceptions\StatusData;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenService extends BaseApiService
{
    /**
     * 设置token 生成机制
     **/
    public function __construct()
    {
        parent::__construct();
        \Config::set('auth.defaults.guard', 'user');
    }

    /**
     * 设置token
     * @param $data  array 用户信息
     * @param $data.username String 账号
     * @param $data.password String 密码$
     * @return array
     * @throws CustomException
     */
    public function setToken(array $data)
    {
        if (! $token = auth('user')->setTTL(60*24*7)->attempt($data)){ // 60*24*30
            throw new CustomException(['message' => 'token生成失败']);
        }
        return $this->respondWithToken($token);
    }

    /**
     * 设置token
     * @param $data  用户信息
     * @param $data.username String 账号
     * @param $data.password String 密码$
     * @return array
     * @throws CustomException
     */
    public function setToken2($user)
    {
        JWTAuth::factory()->setTTL(60*10);
        $token = JWTAuth::fromUser($user);
        return $this->respondWithToken($token);
    }

    /**
     * 设置token
     * @param $data  array 用户信息
     * @param $data.username String 账号
     * @param $data.password String 密码$
     * @return array
     * @throws CustomException
     */
    public function setTokenForever(array $data)
    {
        if (! $token = auth('user')->setTTL(60*60*24*30*12*80)->attempt($data)){
            throw new CustomException(['message' => 'token生成失败']);
        }
        return $this->respondWithToken($token);
    }

    /**
     * 刷新token
     * @return JsonResponse
     * @throws ApiException
     */
    public function refreshToken(): JsonResponse
    {
        try {
            $oldToken = JWTAuth::getToken();
            $token = JWTAuth::refresh($oldToken);
        }catch (TokenBlacklistedException $e) {
            // 这个时候是老的token被拉到黑名单了
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_BLACK,'message'=>MessageData::TOKEN_ERROR_BLACK]);
        }
        return $this->apiSuccess('', $this->respondWithToken($token));
    }

    /**
     * 用户信息
     * @return Object
     */
    public function my():Object
    {
        return JWTAuth::parseToken()->touser();
    }

    /**
     * 用户信息
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        $data = $this->my();
        return $this->apiSuccess('', ['username'=>$data['username']]);
    }

    /**
     * 退出登录
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        JWTAuth::parseToken()->invalidate();
        return $this->apiSuccess('退出成功！');
    }

    /**
     * 组合token数据
     * @param $token
     * @return array
     */
    protected function respondWithToken($token):Array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ];
    }
}
