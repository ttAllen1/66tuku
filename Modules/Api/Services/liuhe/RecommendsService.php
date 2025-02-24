<?php

namespace Modules\Api\Services\liuhe;

use Illuminate\Http\JsonResponse;
use Modules\Api\Models\NumberRecommend;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiMsgData;

class RecommendsService  extends BaseApiService
{
    /**
     * 号码推荐
     * @param $params
     * @return JsonResponse
     */
    public function number_recommend($params): JsonResponse
    {
        $number_recommend = NumberRecommend::query()
//            ->where('year', $params['year'])
            ->where('lotteryType', $params['lotteryType'])
            ->orderBy('year', 'desc')
            ->orderBy('issue', 'desc')
            ->limit(11)
            ->get();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $number_recommend->toArray());
    }
}
