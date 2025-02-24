<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\RoomsRequest;
use Modules\Api\Services\room\RoomService;
use Modules\Common\Exceptions\CustomException;

class RoomController extends BaseApiController
{
    /**
     * 列表
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        return (new RoomService())->list();
    }

    /**
     * 首次加入房间
     * @param RoomsRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function join(RoomsRequest $request): JsonResponse
    {
        $request->validate('join');

        return (new RoomService())->join($request->only(['room_id', 'client_id']));
    }

    /**
     * 切换房间
     * @param RoomsRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function switch(RoomsRequest $request): JsonResponse
    {
        $request->validate('switch');

        return (new RoomService())->switch($request->only(['room_id', 'client_id']));
    }

    /**
     * 聊天
     * @param RoomsRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function chat(RoomsRequest $request): JsonResponse
    {
        $request->validate('chat');

        return (new RoomService())->chat($request->only(['room_id', 'message', 'style', 'type', 'to', 'cate', 'detail_id', 'corpusTypeId']));
    }

    /**
     * 聊天记录
     * @param RoomsRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function record(RoomsRequest $request): JsonResponse
    {
        $request->validate('record');

        return (new RoomService())->record($request->only(['room_id']));
    }

    /**
     * 删除信息
     * @param RoomsRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function delete(RoomsRequest $request): JsonResponse
    {
        $request->validate('delete');

        return (new RoomService())->delete($request->only(['room_id', 'id', 'uuid']));
    }
}
