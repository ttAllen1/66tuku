<?php
/**
 * 统计pv
 * @Description
 */

namespace Modules\Api\Http\Middleware;

use Closure;
use Gai871013\IpLocation\Facades\IpLocation;
use Illuminate\Support\Str;
use Modules\Api\Models\IpView;
use Modules\Api\Models\IpViewUv;
use Modules\Common\Services\BaseService;

class GlobalMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            if ($request->route()->uri == '/api/v1/comment/image'
//                $request->route()->uri == '/api/v1/index/pictures' ||
//                $request->route()->uri == '/api/v1/user/login_3' ||
//                $request->route()->uri == '/api/v1/comment/list_3' ||
//                $request->route()->uri == '/api/v1/comment/children_3' ||
//                $request->route()->uri == '/api/v1/comment/follow_3' ||
//                $request->route()->uri == '/api/v1/comment/follow' ||
//                $request->route()->uri == '/api/v1/comment/create_3' ||
//                $request->route()->uri == '/api/v1/comment/create' ||
//                $request->route()->uri == '/api/v1/user/editUserInfo_3' ||
//                $request->route()->uri == '/api/v1/picture/detail' ||
//                $request->route()->uri == '/api/v1/comment/list'
            ) {
                return $next($request);
            }
            if (Str::contains($request->getHost(), '11.48tkapi.com')) {
                return $next($request);
            }
            if (!$request->isMethod('post') || $request->header('Encipher') != 'enable') {
                return $next($request);
            }
            if (!(new BaseService())->func_is_base64($request->input('data'))) {
                return $next($request);
            }
//            dd($request->input('data'));
//            $decrypt = openssl_decrypt($request->input('data'), env('AES_METHOD'), env('AES_KEY'), 0, env('AES_IV'));
            $decrypt = openssl_decrypt($request->input('data'), config('config.aes_method'), config('config.aes_key'), 0, config('config.aes_iv'));
            if (!$decrypt) {
                return $next($request);
            }
//            $request->request->parameters['data'] = 10;
//            $_POST['abc'] = 'abc';

            $request->merge(json_decode($decrypt, true));
        }catch (\Exception $exception) {
            return $next($request);
        }

        return $next($request);
    }
}
