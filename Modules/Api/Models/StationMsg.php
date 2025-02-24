<?php

namespace Modules\Api\Models;

class StationMsg extends BaseApiModel
{
    protected $primaryKey = 'id';

    protected $guarded = ['user_id'];

    public function userMessage()
    {
        return $this->hasMany(UserMessage::class, 'msg_id', 'id');
    }

    /**
     * 公告｜消息
     * @param array $field
     * @param int $type
     * @return mixed
     */
    static function getMsgList(array $field, int $type, $userinfo)
    {
        if ($type == 2)
        {
            $field[] = 'user_messages.view';
        }
        $station = self::select($field)
            ->orderBydesc('sort')
            ->from('station_msgs')
            ->leftJoin('user_messages', 'station_msgs.id', '=', 'user_messages.msg_id')
            ->where('station_msgs.type', $type)
            ->where('station_msgs.status', 1);
        if ($type == 2)
        {
            $station->where(function ($query) use ($userinfo) {
                $query = $query->where('station_msgs.appurtenant', 1);
                if ($userinfo) {
                    $query->orWhere(['station_msgs.appurtenant' => 2])->where(['user_messages.user_id' => $userinfo->id]);
                }
            });
        }
        return $station->orderByDesc('created_at')
            ->paginate(15)
            ->toArray();
    }

    /**
     * 消息数
     * @return mixed
     */
    static function getMessageBadge()
    {
        if (date('H') == 21 && date('i') > 28 && date('i') < 37) {
            return 0;
        }
        return self::from('station_msgs')
            ->leftJoin('user_messages', 'station_msgs.id', '=', 'user_messages.msg_id')
            ->where('station_msgs.type', 2)
            ->where('station_msgs.status', 1)
            ->where(function ($query) {
                $query->where(['station_msgs.appurtenant' => 2])
                    ->where(['user_messages.user_id' => request()->userinfo->id])
                    ->where(['user_messages.view' => 0]);
            })->count();
    }
}
