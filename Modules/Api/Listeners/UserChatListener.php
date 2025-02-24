<?php

namespace Modules\Api\Listeners;

use Illuminate\Support\Facades\Redis;
use Modules\Api\Events\ChatEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Api\Models\UserChat;

class UserChatListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     * @param ChatEvent $event
     * @return void
     */
    public function handle(ChatEvent $event)
    {
        $sendMsg = $event->sendMsg;
        $room_name = 'chat_room_'.$sendMsg['data']['room_id'];
        $room_count = 'chat_count_'.$sendMsg['data']['room_id'];
        $count = Redis::get($room_count);
        $sendMsg['data']['is_redis_rand'] = $sendMsg['data']['from']['room_id'].'_'.$sendMsg['data']['room_id'].'_'.microtime();
//        $res = Redis::zadd($room_name, strtotime($sendMsg['data']['time']), json_encode($sendMsg));
//        if ($res) {
//            if (!$count) {
//                Redis::set($room_count, 1);
//            } else {
//                Redis::incr($room_count);
//                if ($count+1>300) {
//                    Redis::zpopmin($room_name);
//                }
//            }
//
//        }
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
    }
}
