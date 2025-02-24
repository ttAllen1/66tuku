<?php

namespace Modules\Api\Services\treasure;

use Illuminate\Http\JsonResponse;
use Modules\Api\Services\ad\AdService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class TreasureService extends BaseApiService
{
    /**
     * 获取网址大全 ｜ 担保平台
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function list($params): JsonResponse
    {
        $type = $params['type'];
        $params['lotteryType'] = $params['lotteryType'] ?? 0;
        if ($type != 4 && $type != 5) {
            throw new CustomException(['message'=>'type参数错误']);
        }
        $keyword = $params['keyword'] ?? '';
        $res = (new AdService())->getAdListByPoi([$type], $params['lotteryType'], $keyword);
        if ($type == 4) {
            if ($res) {
                foreach($res['haveImg'] as $k => $v) {
                    $res['haveImg'][$k]['ad_image'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $v['ad_image']);
                }
            }
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
        }
        if ($res) {
            foreach($res as $k => $v) {
                $res[$k]['ad_image'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $v['ad_image']);
            }
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, is_array($res) ? $res : $res->toArray());
    }
}
