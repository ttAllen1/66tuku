<?php
/**
 * 聊天内容
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\chat\ChatService;
use Modules\Common\Exceptions\ApiException;

class ChatController extends BaseApiController
{
    public function list(Request $request)
    {
        return (new ChatService())->list($request->all());
    }

    public function delete(Request $request)
    {
        return (new ChatService())->delete($request->only(['id', 'room_id', 'uuid']));
    }

    public function check(Request $request)
    {
        return (new ChatService())->check($request->all());
    }

    /**
     * 机器人列表
     * @param Request $request
     * @return JsonResponse
     */
    public function chat_robot_list(Request $request): JsonResponse
    {
        return (new ChatService())->chat_robot_list($request->all());
    }

    /**
     * 创建机器人
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function chat_robot_store(Request $request): JsonResponse
    {
        return (new ChatService())->chat_robot_store($request->all());
    }

    /**
     * 智能配置数据
     * @return JsonResponse
     */
    public function chat_smart_list(): JsonResponse
    {
        return (new ChatService())->chat_smart_list();
    }

    /**
     * 智能配置保存
     * @param Request $request
     * @return JsonResponse
     */
    public function chat_smart_store(Request $request): JsonResponse
    {
        return (new ChatService())->chat_smart_store($request->all());
    }
}
