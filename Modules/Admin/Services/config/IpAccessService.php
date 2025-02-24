<?php

/**
 * IP访问服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\AuthConfig;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\InvalidDomain;
use Modules\Api\Models\IpView;
use Modules\Common\Services\BaseService;

class IpAccessService extends BaseApiService
{
    /**
     * 访问清单
     * @description
     **/
    public function index(array $data): JsonResponse
    {
        $city = $data['area'] ?? '';
        $date_range= $data['date_range'] ?? '';
        if ($date_range) {
            $date_range[0] = Carbon::parse($date_range[0])->format('Y-m-d');
            $date_range[1] = Carbon::parse($date_range[1])->format('Y-m-d');
        }

        $list = IpView::query()
            ->when($city, function($query) use ($city) {
                $query->where('city', 'like', '%'.$city.'%');
            })
            ->when($date_range, function($query) use ($date_range) {
                $query->whereBetween('created_at', [$date_range[0], $date_range[1]]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'])
            ->toArray();

        if ($list['data']) {
            $service = (new BaseService());
            foreach ($list['data'] as $k => $v) {
                if ($v['type'] == 1) {
                    $list['data'][$k]['ip'] = long2ip($v['ip']);
                } else {
                    $list['data'][$k]['ip'] = $service->long2ip6($v['ip']);
                }
            }
        }

        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }
    /**
     * 失效域名
     * @description
     **/
    public function invalid_domains(array $data): JsonResponse
    {
        $ip_area = $data['ip_area'] ?? '';
        $ip = $data['ip'] ?? '';
        $domain = $data['domain'] ?? '';
        $device = $data['device'] ?? 0;
        $date_range= $data['date_range'] ?? '';
        if ($date_range) {
            $date_range[0] = Carbon::parse($date_range[0])->format('Y-m-d');
            $date_range[1] = Carbon::parse($date_range[1])->format('Y-m-d');
        }

        $list = InvalidDomain::query()
            ->when($device, function($query) use ($device) {
                $query->where('device', $device);
            })
            ->when($domain, function($query) use ($domain) {
                $query->where('domain', 'like', '%'.$domain.'%');
            })
            ->when($ip, function($query) use ($ip) {
                $query->where('ip', 'like', '%'.$ip.'%');
            })
            ->when($ip_area, function($query) use ($ip_area) {
                $query->where('ip_area', 'like', '%'.$ip_area.'%');
            })
            ->when($date_range, function($query) use ($date_range) {
                $query->whereBetween('created_at', [$date_range[0], $date_range[1]]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'])
            ->toArray();

        $config_js = AuthConfig::query()->where('id', 1)->value('config_add_js');

//        $urlArray = explode("\n", $config_js);
        $pattern = "/'(https?:\/\/[^'\s]+)'/";
        preg_match_all($pattern, $config_js, $matches);
        $urlArray = $matches[1];
        $domainBody = [];
        foreach($urlArray as $k => $v) {
            $urlInfo = parse_url($v);
            $infoArr = explode('.', $urlInfo['host']);
            if (!in_array($infoArr[1], $domainBody)) {
                $domainBody[$infoArr[1]][$k]['name'] = $infoArr[0];
                $domainBody[$infoArr[1]][$k]['domain'] = $v;
            } else {
                if (!in_array($infoArr[0], $domainBody[$infoArr[1]])) {
                    $domainBody[$infoArr[1]][$k]['name'] = $infoArr[0];
                    $domainBody[$infoArr[1]][$k]['domain'] = $v;
                }
            }
        }
        $api_names = [];
        foreach ($domainBody as $k => $v) {
            sort($domainBody[$k]);
            $api_names[$k] = array_column($domainBody[$k], 'name');
//            sort($api_names);
        }
//        dd($domainBody, $api_names);
//        dd($config_js, $matches[1], $domainBody, $infoArr);
//        array_pop($urlArray);
//        array_shift($urlArray);
//        $domains = [];
//        foreach($urlArray as $k => $v) {
//            $url =trim(trim($v), "',");
//            $host = parse_url($url);
//
//            $post = $host['port'] ?? '';
//            $hostArr = explode('.', $host['host']);
//            $domains[$k]['name'] = $hostArr[0];
//            if ($post) {
//                $domains[$k]['domain'] = 'https://'.$host['host'].":".$post;
//            } else {
//                $domains[$k]['domain'] = 'https://'.$host['host'];
//            }
//        }


//        $domains = [
//            [
//                "name"  => 'api',
////                "domain" => 'https://api.49tkapi8.com:8443'
//                "domain" => 'https://api.49tkapi7.com:8443'
//            ],
//            [
//                "name"  => 'api1',
////                "domain" => 'https://api1.49tkapi8.com:8443'
//                "domain" => 'https://api1.49tkapi7.com:8443'
//            ],
//            [
//                "name"  => 'api2',
////                "domain" => 'https://api2.49tkapi8.com:8443'
//                "domain" => 'https://api2.49tkapi7.com:8443'
//            ],
//            [
//                "name"  => 'api3',
////                "domain" => 'https://api3.49tkapi8.com:8443'
//                "domain" => 'https://api3.49tkapi7.com:8443'
//            ],
//            [
//                "name"  => 'api4',
////                "domain" => 'https://api4.49tkapi8.com:8443'
//                "domain" => 'https://api4.49tkapi7.com:8443'
//            ]
//        ];
        $data = [];
        foreach ($domainBody as $k => $v) {
            foreach($v as $kk => $vv) {
                $data[$k][$vv['name']] = InvalidDomain::query()->select('domain', 'ip_area')
                    ->selectRaw('COUNT(*) AS ip_area_count')
                    ->groupBy('domain', 'ip_area')
                    ->orderByDesc('ip_area_count')
                    ->where('domain', $vv['domain'])
                    ->limit(10)
                    ->get()->toArray();
            }
        }
//        dd($data);

        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total'],
            'tongji'        => $data,
            'api_names'     => $api_names,
        ]);
    }
}
