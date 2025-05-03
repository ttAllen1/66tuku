<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\RedPacketRequest;
use Modules\Api\Services\room\RedPacketService;
use Modules\Common\Exceptions\CustomException;

class RedPacketController extends BaseApiController
{
    /**
     * 红包列表
     * @param RedPacketRequest $request
     * @return JsonResponse
     */
    public function list(RedPacketRequest $request): JsonResponse
    {
        return (new RedPacketService())->list($request->only(['room_id']));
    }

    /**
     * 抢红包
     * @param RedPacketRequest $request
     * @return null
     * @throws CustomException
     */
    public function receive(RedPacketRequest $request)
    {
        $request->validate('receive');

        return (new RedPacketService())->receive($request->all());
    }

    public function receives(RedPacketRequest $request)
    {
        $request->validate('receives');

        return (new RedPacketService())->receives($request->all());
    }

    public function ranks()
    {
        return (new RedPacketService())->ranks();
    }

    public function index_red()
    {
        return (new RedPacketService())->index_red();
    }
}
