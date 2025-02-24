<?php
/**
 * @Name 管理员信息服务
 * @Description
 */

namespace Modules\Admin\Services\auth;
use Modules\Admin\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\MessageData;
use Modules\Common\Exceptions\StatusData;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class TokenService extends BaseApiService
{
    /**
     * @name 设置token 生成机制
     * @description
     * @return JSON
     **/
    public function __construct()
    {
        \Config::set('auth.defaults.guard', 'auth_admin');
        \Config::set('jwt.ttl', 600);
    }
    /**
     * @name 设置token
     * @description
     * @param data  Array 用户信息
     * @param data.username String 账号
     * @param data.password String 密码$
     * @return JSON | Array
     **/
    public function setToken($data){
        JWTAuth::factory()->setTTL(60*24*10);
        if (! $token = JWTAuth::attempt($data)){
            $this->apiError('token生成失败');
        }
        return $this->respondWithToken($token);
    }

    /**
     * @name 刷新token
     * @description
     *
     * @throws ApiException
     */
    public function refreshToken()
    {
        try {
            $oldToken = JWTAuth::getToken();
            $token = JWTAuth::refresh($oldToken);
        }catch (TokenBlacklistedException $e) {
            // 这个时候是老的token被拉到黑名单了
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_BLACK,'message'=>MessageData::TOKEN_ERROR_BLACK]);
        } catch (TokenExpiredException $e) {
            //token已过期
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_BLACK,'message'=>MessageData::TOKEN_ERROR_JWT]);
        }
        return $this->apiSuccess('', $this->respondWithToken($token));
    }
    /**
     * @name 管理员信息
     * @description
     * @return Array
     **/
    public function my():Object
    {
        return JWTAuth::parseToken()->touser();
    }
    /**
     * @name
     * @description
     * @method  GET
     * @param
     * @return JSON
     **/
    public function info()
    {
        $data = $this->my();
        return $this->apiSuccess('',['username'=>$data['username']]);
    }
    /**
     * @name 退出登录
     * @description
     * @return JSON
     **/
    public function logout()
    {
        JWTAuth::parseToken()->invalidate();
        return $this->apiSuccess('退出成功！');
    }

    /**
     * @name 组合token数据
     * @description
     **/
    protected function respondWithToken($token):Array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ];
    }
}
