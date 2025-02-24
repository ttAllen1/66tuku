<?php

namespace Modules\Api\Services\room;

use GatewayClient\Gateway;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Api\Events\ChatEvent;
use Modules\Api\Models\ChatRoom;
use Modules\Api\Models\User;
use Modules\Api\Models\UserChat;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\picture\PictureService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class RoomService extends BaseApiService
{
    public function __construct()
    {
        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    /**
     * 加入房间
     * @param $params
     * @param string $type
     * @param string $message
     * @return JsonResponse
     * @throws CustomException
     */
    public function join($params, string $type='join_ok', string $message='房间加入成功'): JsonResponse
    {
        $client_id  = $params['client_id'];
        $room_id    = $params['room_id'];
        $user_id    = auth('user')->id();
        if (Redis::get("chat_room_status_".$room_id) ==2) {
            throw new CustomException(['message'=>'此聊天室已被停用']);
        }
        if ($fromSession = Gateway::getSession($client_id)) {
            Gateway::updateSession($client_id, [
                'room_id'       => $room_id,
            ]);
            Gateway::leaveGroup($client_id, $fromSession['room_id']);
            Gateway::joinGroup($client_id, $room_id);
            Gateway::bindUid($client_id, $user_id);
            Gateway::sendToUid($user_id, json_encode(['type'=>$type, 'message'=>$message]));
            $this->sengTongJi();
            return $this->apiSuccess();
//            return response()->json();
        }
        $this->_bind($client_id, $user_id, $room_id, $type, $message);

        return $this->apiSuccess();
    }

    /**
     * 聊天室房间列表
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $roomList = ChatRoom::query()->where('status', 1)->get();

        return $this->apiSuccess('', $roomList->toArray());
    }

    /**
     * 切换房间
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function switch($params): JsonResponse
    {
        $client_id  = $params['client_id'];
        $user_id    = auth('user')->id();
        $room_id    = $params['room_id'];
        $message = '房间切换成功';
        $type = 'switch_ok';
        if (Redis::get("chat_room_status_".$room_id) ==2) {
            throw new CustomException(['message'=>'此聊天室已被停用']);
        }
        if (!$fromSession = Gateway::getSession($client_id)) {
            return $this->join($params, $type, $message);
        }
        Gateway::leaveGroup($client_id, $fromSession['room_id']);
        Gateway::updateSession($client_id, [
            'room_id'       => $room_id,
        ]);
        Gateway::joinGroup($client_id, $room_id);
        Gateway::bindUid($client_id, $user_id);
        Gateway::sendToUid($user_id, json_encode(['type'=>$type, 'message'=>$message]));
        $this->sengTongJi();

        return response()->json();
    }

    /**
     * 聊天核心
     * @param $params
     * @param int $assignFromId
     * @param array $clientId
     * @return JsonResponse
     * @throws CustomException
     */
    public function chat($params, int $assignFromId=0, array $clientId=[]): JsonResponse
    {
        if ($params['style'] == 'red_receive_ok') {
            return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);
        }
        if ($assignFromId) {
            $from = $assignFromId;
        } else {
            $from = auth('user')->id();
            $register_at = DB::table('users')->where('id', $from)->value('register_at');
            if ((strtotime($register_at) + 3600*3) > time() ) {
                throw new CustomException(['message'=>'新注册用户三个小时后才能聊天哦']);
            }
            $clientId = Gateway::getClientIdByUid($from);
        }
        if (!$clientId) {
            throw new CustomException(['message'=>'当前客户已下线，请尝试重连']);
        }
        $fromSession = Gateway::getSession($clientId[0]);

        if ( !$assignFromId && Redis::get("chat_room_status_".$fromSession['room_id']) ==2) {
            throw new CustomException(['message'=>'此聊天室暂时不能聊天哦']);
        }
        $params['uuid'] = Str::uuid();
        $sendMsg = $this->sendMsg('chat_ok', $fromSession, $params);

        $room_check = Redis::get("chat_room_check_".$fromSession['room_id']);
//        $room_check = 0;
        if ($room_check==0) {
            Gateway::sendToGroup($fromSession['room_id'], json_encode(['type'=>$sendMsg['type'], 'data'=>$sendMsg['data']]));
        } else {
            Gateway::sendToUid($from, json_encode(['type'=>$sendMsg['type'], 'data'=>$sendMsg['data']]));
        }

        // 保存数据
        $sendMsg['from_user_id'] = $from;

        $sendMsg['status'] = $room_check == 0 ? 1 : 0;
//        event(new ChatEvent($sendMsg));
        UserChat::query()
            ->insert([
                'from_user_id'      => $sendMsg['from_user_id'],
                'status'            => $sendMsg['status'],
                'from'              => json_encode($sendMsg['data']['from']),
                'to'                => json_encode($sendMsg['data']['to']),
                'room_id'           => $sendMsg['data']['room_id'],
                'style'             => $sendMsg['data']['style'],
                'type'              => $sendMsg['data']['type'],
                'message'           => is_array($sendMsg['data']['message']) ? json_encode($sendMsg['data']['message']) : strip_tags($sendMsg['data']['message']),
                'img_width'         => $sendMsg['data']['img_width'],
                'img_height'        => $sendMsg['data']['img_height'],
                'cate'              => $sendMsg['data']['cate'],
                'corpusTypeId'      => $sendMsg['data']['corpusTypeId'],
                'uuid'              => $sendMsg['data']['uuid'],
                'detail_id'         => $sendMsg['data']['detail_id'],
                'created_at'        => $sendMsg['data']['time'],
            ]);
        // 机器人自动回复
        if ($params['type'] == 'at') {
            $is_chat = DB::table('users')->where('id', $params['to']['id'])->value('is_chat');

            if ($is_chat) {
                sleep(1);
                $this->auto_reply($params['to']['id']);
                // 自动回复 新建个方法 休眠一秒
            }
        }

        return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);
    }

    public function auto_reply($reply_user_id)
    {
        $room_id = 5;
        $userData = DB::table('users')->where('id', $reply_user_id)->select([
            'id', 'nickname', 'avatar'
        ])->first();
        $userData = (array)$userData;
        $from = $userData['id'];
        // 发送到聊天室
        $arr = ['很好', '不错', '可以'];
        $sendData = [
            'room_id' => $room_id,
            'message' => $arr[array_rand($arr)],
            'style'   => 'string',
            'type'    => 'all',
            'to'      => [],
        ];

        $sendData['uuid'] = Str::uuid();

        $fromSession = [
            'user_id' => $from,
            'user_name' => $userData['nickname'],
            'avatar' => $userData['avatar'],
            'room_id' => $room_id,
        ];
        $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData, false);

        Gateway::sendToGroup($fromSession['room_id'], json_encode([
            'type' => $sendMsg['type'], 'data' => $sendMsg['data']
        ]));

        // 保存数据
        $sendMsg['from_user_id'] = $from;

        $sendMsg['status'] = 1;
        UserChat::query()
            ->insert([
                'from_user_id' => $sendMsg['from_user_id'],
                'status'       => $sendMsg['status'],
                'from'         => json_encode($sendMsg['data']['from']),
                'to'           => json_encode($sendMsg['data']['to']),
                'room_id'      => $sendMsg['data']['room_id'],
                'style'        => $sendMsg['data']['style'],
                'type'         => $sendMsg['data']['type'],
                'message'      => is_array($sendMsg['data']['message']) ? json_encode($sendMsg['data']['message']) : strip_tags($sendMsg['data']['message']),
                'img_width'    => $sendMsg['data']['img_width'],
                'img_height'   => $sendMsg['data']['img_height'],
                'cate'         => $sendMsg['data']['cate'],
                'corpusTypeId' => $sendMsg['data']['corpusTypeId'],
                'uuid'         => $sendMsg['data']['uuid'],
                'detail_id'    => $sendMsg['data']['detail_id'],
                'created_at'   => $sendMsg['data']['time'],
            ]);
    }

    /**
     * 聊天记录
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function record($params): JsonResponse
    {
        $page = request()->input('page');
//        if ($page<=20) {
//            $data = $this->chat_record_redis($params['room_id'], $page);
//        } else {
//            $data = $this->chat_record_db($params['room_id']);
//        }
        $data = $this->chat_record_db($params['room_id']);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data);
    }

    /**
     * 删除聊天记录 支持数组
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function delete($params): JsonResponse
    {
        try {
            $uuids   = $params['uuid'] ?? [];
            if (!$uuids) {
                throw new CustomException(['message'=>'uuid必传']);
            }
            $page = request()->input('page');

            // 删除数据库数据
            $this->chat_delete_by_db_id($params['room_id'], $uuids);
            // 删除redis数据
//            if ($page<=20) {
//                $chats = $this->chat_by_redis_page($params['room_id'], $page);
//                foreach ($chats as $k => $chat) {
//                    if ( in_array($chat['data']['uuid'], $uuids)) {
//                        $room_name = 'chat_room_'.$params['room_id'];
//                        Redis::zremrangebyscore($room_name, strtotime($chat['data']['time']), strtotime($chat['data']['time']));
//                    }
//                }
//            }
        }catch (\Exception $exception) {
            Log::error('聊天室删除失败', ['message'=>$exception->getMessage()]);
            throw new CustomException(['message'=>'消息删除失败']);
        }
        // 通知客户端 删除对应消息
        Gateway::sendToGroup($params['room_id'], json_encode(['type'=>'delete', 'data'=>['uuids'=>$uuids]]));

        return $this->apiSuccess(ApiMsgData::DELETE_API_SUCCESS);
    }

    /**
     * 审核聊天记录 支持数组
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function check($params): JsonResponse
    {
        try {
            $uuids   = $params['uuid'] ?? [];
            if (!$uuids) {
                throw new CustomException(['message'=>'uuid必传']);
            }
            $page = request()->input('page');

            // 修改数据库数据
            $this->chat_check_by_db_id($params['room_id'], $uuids);
            // 修改redis数据
//            if ($page<=20) {
//                $chats = $this->chat_by_redis_page_all($params['room_id'], $page);
//                foreach ($chats as $chat) {
//                    if ( in_array($chat['data']['uuid'], $uuids)) {
//                        $room_name = 'chat_room_'.$params['room_id'];
//                        $chat['status'] = 1;
//                        Redis::zremrangebyscore($room_name, strtotime($chat['data']['time']), strtotime($chat['data']['time']));
//                        Redis::zadd($room_name, strtotime($chat['data']['time']), json_encode($chat));
//                    }
//                }
//            }
        }catch (\Exception $exception) {
            Log::error('聊天室消息审核失败', ['message'=>$exception->getMessage()]);
            throw new CustomException(['message'=>'消息审核失败']);
        }
        // 通知客户端 删除对应消息
//        Gateway::sendToGroup($params['room_id'], json_encode(['type'=>'delete', 'data'=>['uuids'=>$uuids]]));

        return $this->apiSuccess(ApiMsgData::STATUS_API_SUCCESS);
    }

    /**
     * 处理返回对象
     * @param $type
     * @param $fromSession
     * @param $params
     * @param bool $isCheck
     * @return array
     * @throws CustomException
     */
    public function sendMsg($type, $fromSession, $params, bool $isCheck=true): array
    {
        if ($isCheck) {
            if (!is_array($params['message'])) {
                if (Str::startsWith($params['message'], 'http') || Str::startsWith($params['message'], '//')) {
                    throw new CustomException(['message'=>'消息非法']);
                }
            }
        }

        $arr = [];
        $arr['type'] = $type;
        $arr['data']['style'] = $params['style'];
        $arr['data']['from'] = $fromSession;
        if ($params['type'] == 'all') {
            $arr['data']['to'] = [];
        } else {
            $arr['data']['to']['to_id'] = $params['to']['id'];
            $arr['data']['to']['to_name'] = $params['to']['name'];
        }
        $arr['data']['img_width']         = 0;
        $arr['data']['img_height']        = 0;
        $arr['data']['img_mime']          = '';
        if ($params['style'] == 'image') {
            $imageInfo = (new PictureService())->getImageInfoWithOutHttp($params['message'], false, $isCheck);
            $arr['data']['img_width']         = $imageInfo['width'];
            $arr['data']['img_height']        = $imageInfo['height'];
            $arr['data']['img_mime']          = $imageInfo['mime'];
        }

        $arr['data']['room_id']         = $params['room_id'];
        $arr['data']['message']         = $params['message'];
        $arr['data']['cate']            = $params['cate'] ?? 0;
        $arr['data']['detail_id']       = $params['detail_id'] ?? 0;
        $arr['data']['corpusTypeId']    = $params['corpusTypeId'] ?? 0;
        $arr['data']['uuid']            = $params['uuid'];
        $arr['data']['type']            = $params['type'];
        $arr['data']['time']            = Carbon::now()->format('Y-m-d H:i:s');

        return $arr;
    }

    /**
     * @throws CustomException
     */
    public function _bind($client_id, $user_id, $room_id, $type, $message)
    {
        // 绑定uid和client_id、加入房间
        Gateway::bindUid($client_id, $user_id);
        try{
            $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->findOrFail($user_id);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'当前绑定用户不存在']);
        }
        // 记录会话
        Gateway::setSession($client_id, [     // GatewayWorker 负责
            'user_id'           => $user_id,
            'user_name'         => $userData->nickname,
            'avatar'            => $userData->avatar,
            'room_id'           => $room_id,
//            'is_forbid_speak'   => $userData->is_forbid_speak
        ]);
        Gateway::joinGroup($client_id, $room_id);
        Gateway::sendToUid($user_id, json_encode(['type'=>$type, 'message'=>$message]));
        $this->sengTongJi();
    }

    /**
     * 数据库 分页 聊天记录
     * @param $room_id
     * @return array
     * @throws CustomException
     */
    private function chat_record_db($room_id): array
    {
        $res = UserChat::query()
            ->where('room_id', $room_id)
            ->where(function($query) {
                $query->where('status', 1)
                    ->orWhere(function ($query){
                        $query->where('status', 0)->where('from_user_id', auth('user')->id());
                    });
            })
            ->orderBy('created_at', 'desc')
            ->simplePaginate();

        if ($res->isEmpty()) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        $res = $res->toArray()['data'];

        $userId = auth('user')->id();
        $chats = [];
        $redIds = [];
        foreach ($res as $k => $v) {
            if ($v['style']=='red_envelope') {
                $v['message'] = json_decode($v['message'], true);
                $redIds[] = $v['message']['redId'];
            } else if ($v['style']=='sys_report_code') {
                $v['message'] = json_decode($v['message'], true);
            }
            $chats[$k]['type'] = 'chat_ok';
            $chats[$k]['data'] = $v;
            $chats[$k]['data']['time'] = $v['created_at'];
            unset($chats[$k]['data']['created_at'], $chats[$k]['data']['updated_at']);
        }
        if ($redIds && $userId) {
            $userRedsRes = DB::table('user_reds')
                ->where('user_id', $userId)
                ->pluck('red_id')->toArray();
            if ($userRedsRes) {
                foreach ($chats as $k => $v) {
                    if ($v['data']['style'] == 'red_envelope') {
                        $message = $v['data']['message'];
                        if ( in_array($message['redId'], $userRedsRes) ) {
                            $message['is_receive'] = true;
                            $chats[$k]['data']['message'] = $message;
                        }
                    }
                }
            }
        }

        return $chats;
    }

    /**
     * redis 分页 聊天数据
     * @param $room_id
     * @param $page
     * @return mixed
     * @throws CustomException
     */
    private function chat_record_redis($room_id, $page)
    {
        $chats = $this->chat_by_redis_page($room_id, $page);
//        foreach($chats as $k => $chat) {
//            $data = json_decode($chat, true);
//            $chats[$k] = $data;
//        }
        $this->sortByKey($chats, 'time');
        sort($chats);
        return $chats;
    }

    /**
     * 获取redis中 某一页的聊天数据 排除为审核的
     * @param $room_id
     * @param $page
     * @return mixed
     * @throws CustomException
     */
    private function chat_by_redis_page($room_id, $page)
    {
        $room_name = 'chat_room_'.$room_id;
        $perPage = 15;
        $start = ($page - 1) * $perPage;
        $end = $start + $perPage - 1;
        if (!Redis::exists($room_name)) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        $chats = Redis::zrevrange($room_name, $start, $end);
        if ($chats) {
            foreach($chats as $k => $chat) {
                $data = json_decode($chat, true);
                if ($data['status'] == 0 && $data['data']['from']['user_id'] != auth('user')->id()) {
                    unset($chats[$k]);
                } else {
                    $chats[$k] = $data;
                }
            }
            if ($chats) {
                return $chats;
            }
            if (++$page<=20) {
                $this->chat_by_redis_page($room_id, $page);
            }
        }
        throw new CustomException(['message'=>'数据不存在']);
    }

    /**
     * 获取redis中 某一页的聊天数据 所有数据
     * @param $room_id
     * @param $page
     * @return mixed
     * @throws CustomException
     */
    private function chat_by_redis_page_all($room_id, $page)
    {
        $room_name = 'chat_room_'.$room_id;
        $perPage = 15;
        $start = ($page - 1) * $perPage;
        $end = $start + $perPage - 1;
        if (!Redis::exists($room_name)) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        $chats = Redis::zrevrange($room_name, $start, $end);
        if ($chats) {
            foreach($chats as $k => $chat) {
                $data = json_decode($chat, true);

                $chats[$k] = $data;
            }
            return $chats;
        }
        throw new CustomException(['message'=>'数据不存在']);
    }

    /**
     * 根据id删除数据库中的聊天数据
     * @param $room_id
     * @param $uuids
     * @return void
     */
    private function chat_delete_by_db_id($room_id, $uuids): void
    {
        $uuids = is_array($uuids) ? $uuids : [$uuids];

        UserChat::query()->where('room_id', $room_id)
            ->whereIn('uuid', $uuids)
            ->delete();
    }

    private function sengTongJi()
    {
        $groupIdList = Gateway::getAllGroupIdList();
        $arr = [];
        if ($groupIdList) {
            foreach ($groupIdList as $group_id) {
                $arr[$group_id]['room_id'] = $group_id;
                $arr[$group_id]['count'] = count(Gateway::getClientIdListByGroup($group_id));
            }
        }
        sort($arr);
        Redis::set('chat_room_count_online', json_encode($arr));
    }

    private function chat_check_by_db_id($room_id, $uuids): void
    {
        $uuids = is_array($uuids) ? $uuids : [$uuids];

        UserChat::query()->where('room_id', $room_id)
            ->whereIn('uuid', $uuids)
            ->update(['status'=>1]);
    }
}
