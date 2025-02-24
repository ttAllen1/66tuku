<?php
/**
 * @Name  用户验证中间件
 * @Description
 */

namespace Modules\Api\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\UserGame;
use Modules\Api\Services\game\GameService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Exceptions\MessageData;
use Modules\Common\Exceptions\StatusData;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use JWTAuth;
class UserApiAuth
{
    public function handle($request, Closure $next)
    {
        if (Redis::get('sys_update')==='1') {
            throw new ApiException(['status'=>40001,'message'=>'网站升级维护中，请稍后访问']);
        }
//        throw new ApiException(['status'=>40009,'message'=>'网站升级维护中，请稍后访问']);
        \Config::set('auth.defaults.guard', 'user');
        \Config::set('jwt.ttl', 60*60*24);
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {  //获取到用户数据，并赋值给$user   'msg' => '用户不存在'
                throw new ApiException(['status'=>StatusData::TOKEN_ERROR_SET,'message'=>MessageData::TOKEN_ERROR_SET]);
            }
        }catch (TokenBlacklistedException $e) {
            // 这个时候是老的token被拉到黑名单了
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_BLACK,'message'=>MessageData::TOKEN_ERROR_BLACK]);
        } catch (TokenExpiredException $e) {
            //token已过期
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_EXPIRED,'message'=>MessageData::TOKEN_ERROR_EXPIRED]);
        } catch (TokenInvalidException $e) {
            //token无效
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_JWT,'message'=>MessageData::TOKEN_ERROR_JWT]);
        } catch (JWTException $e) {
            //'缺少token'
            throw new ApiException(['status'=>StatusData::TOKEN_ERROR_JTB,'message'=>MessageData::TOKEN_ERROR_JTB]);
        }
        if (Redis::sismember('blacklist_users', $user['id'])) {
            // 用户被拉黑
            throw new CustomException(['status'=>40001, 'message'=>MessageData::USER_IS_FORBID]);
        }
        $request->userinfo = $user;
        if ($request->userinfo->status == 2) {
            throw new CustomException(['status'=>40001, 'message'=>MessageData::USER_IS_FORBID]);
        }
        $lastRechargeType = UserGame::lastRechargeType($user->id);
        $request->ky_linecode = AuthGameConfig::val('ky_linecode');
        if ($lastRechargeType != null && $lastRechargeType != 0) {
            (new GameService())->transferOut($lastRechargeType, $request->ky_linecode . $user->account_name, $user->id);
        }
        return $next($request);
    }
}
