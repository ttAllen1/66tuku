<?php

namespace Modules\Api\Services\statistics;

use Illuminate\Http\JsonResponse;
use Modules\Api\Models\InvalidDomain;
use Modules\Api\Models\Statistics;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\CustomException;

class StatisticsService extends BaseApiService
{
    /**
     * @param $params
     * @return JsonResponse
     */
    public function statistics($params): JsonResponse
    {
        try{
            Statistics::query()->create($params);
        }catch (\Exception $exception) {
            return $this->apiSuccess();
        }
        return $this->apiSuccess();
    }

    /**
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function domain($params): JsonResponse
    {
        if (!in_array($params['device'], [1, 2, 3])) throw new CustomException(['message'=>'设备不允许']);
        if (is_string($params['domain'])) {
            $params['domain'] = explode(',', $params['domain']);
        }
        $params['domain'] = is_array($params['domain']) ? $params['domain'] : [$params['domain']];
        if (!$params['domain']) {
            throw new CustomException(['message'=>'域名不能为空']);
        }
        $data = [];
        foreach ($params['domain'] as $k => $v) {
            $data[$k]['domain'] = $v;
            $data[$k]['ip'] = $this->getIp();
            $data[$k]['ip_area'] = $this->getIpInCountry();
            $data[$k]['device'] = $params['device'];
            $data[$k]['created_at'] = date('Y-m-d H:i:s');
        }
        InvalidDomain::query()->insert($data);

        return $this->apiSuccess();
    }
}
