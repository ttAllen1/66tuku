<?php

namespace Modules\Admin\Services\chat;

use GatewayClient\Gateway;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\ChatRoom;
use Modules\Admin\Services\BaseApiService;

class RoomService extends BaseApiService
{
    public function __construct()
    {
        Gateway::$registerAddress = '127.0.0.1:1238';
        parent::__construct();
    }

    public function room($data)
    {
        $rooms = ChatRoom::query()
            ->paginate($data['limit'])
            ->toArray();
        $groupIdList = Gateway::getAllGroupIdList();
        $chat_room_count_onlines = [];
        if ($groupIdList) {
            foreach ($groupIdList as $group_id) {
                $chat_room_count_onlines[$group_id]['room_id'] = $group_id;
                $chat_room_count_onlines[$group_id]['count'] = count(Gateway::getClientIdListByGroup($group_id));
            }
        }
        sort($chat_room_count_onlines);

        if ($rooms['data']) {
            foreach ($rooms['data'] as $kk => $room) {
                $rooms['data'][$kk]['onlines'] = 0;
                if ($chat_room_count_onlines) {
                    foreach ($chat_room_count_onlines as $v) {
                        if ($room['id'] == $v['room_id']) {
                            $rooms['data'][$kk]['onlines'] = $v['count'];
                        }
                    }
                }
            }
        }

        return $this->apiSuccess('',[
            'list'          => $rooms['data'],
            'total'         => $rooms['total'],
        ]);
    }

    public function update(int $id,array $data){
        Gateway::$registerAddress = '127.0.0.1:1238';
        if ($data['status'] ==1) {
            // 启用
            Redis::set("chat_room_status_".$id, $data['status']);
            Gateway::sendToGroup($id, json_encode(array(
                'type'      => 'room_start',
                'room_id'   => $id
            )));
        } else if ($data['status'] ==2) {
            // 禁用
            Redis::set("chat_room_status_".$id, $data['status']);
            Gateway::sendToGroup($id, json_encode(array(
                'type'      => 'room_stop',
                'room_id'   => $id
            )));
        }

        return $this->commonUpdate(ChatRoom::query(),$id,$data);
    }

    /**
     * 审核
     * @param int $id
     * @param array $data
     * @return \Illuminate\Http\JsonResponse|null
     */
    public function check(int $id,array $data){
        Redis::set("chat_room_check_".$id, $data['is_check']);
        return $this->commonUpdate(ChatRoom::query(),$id,$data);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {

        return $this->commonCreate(ChatRoom::query(), $data);
    }

}
