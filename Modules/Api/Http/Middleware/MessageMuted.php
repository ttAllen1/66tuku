<?php
/**
 * @Name  屏蔽禁言者发言
 * @Description
 */

namespace Modules\Api\Http\Middleware;

use Closure;
use Modules\Api\Services\user\UserMushinService;
use Modules\Common\Exceptions\CustomException;

class MessageMuted
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws CustomException
     */
    public function handle($request, Closure $next)
    {
        if (auth('user')->user()['is_forbid_speak'] ==1) {
            throw new CustomException(['message'=>'禁言中，禁止操作']);
        }

        $isMushin = (new UserMushinService())->userIfMushin();
        if ($isMushin) {
            throw new CustomException(['message'=>'小黑屋中，禁止操作']);
        }

        return $next($request);
    }
}
