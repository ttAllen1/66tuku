<?php

namespace Modules\Api\Services\lottery;

use GatewayClient\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiMsgData;

class OpenLotteryService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    /**
     * 获取某彩种最新号码信息【客户端主动拉】
     * @param $params
     * @return JsonResponse
     */
    public function real_open($params): JsonResponse
    {
        $lotteryType = $params['lotteryType'];
        foreach ([1, 2, 3, 4, 5] as $v) {
            if ($v != $lotteryType) {
                Gateway::leaveGroup($params['client_id'], $v);
            }
        }
        Gateway::joinGroup($params['client_id'], $lotteryType);
        // 'real_open_'.$lotteryType redis 里数据最新且仅有一份
        $body = Redis::get('real_open_'.$lotteryType);
        Gateway::sendToClient($params['client_id'], json_encode(array(
            'type'      => 'real_open',
            'data'    => [
                'lotteryType'   => $lotteryType,
                'body'          => $body
            ]
        )));

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS);
    }
}
