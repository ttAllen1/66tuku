<?php
/**
 * 统计pv
 * @Description
 */

namespace Modules\Api\Http\Middleware;

use Closure;
use Gai871013\IpLocation\Facades\IpLocation;
use Modules\Api\Models\IpView;
use Modules\Api\Models\IpViewUv;
use Modules\Common\Services\BaseService;

class ViewsMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            $ip = (new BaseService())->getClientIp();
            if (!$ip) {
                return $next($request);
            }
            // 判断是ipv4 还是 ipv6
            $type = filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? 1 : 2;
            $ipAddress = IpLocation::getLocation($ip);
            $city = $ipAddress['country'] ?? '';
            $ip = $type == 1 ? ip2long($ip) : (new BaseService())->ip2long6($ip);

            $year = date('Y');
            $month = date('m');
            $day = date('d');
            $date = "$year-$month-$day";
            // pv
            IpView::query()->insert([
                'ip'         => $ip,
                'type'       => $type,
                'city'       => $city,
                'year'       => $year,
                'month'      => $month,
                'day'        => $day,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // uv
            IpViewUv::query()->insert([
                'ip'         => $ip,
                'type'       => $type,
                'year'       => $year,
                'month'      => $month,
                'day'        => $day,
                'date'       => $date,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }catch (\Exception $exception) {
            return $next($request);
        }

        return $next($request);
    }
}
