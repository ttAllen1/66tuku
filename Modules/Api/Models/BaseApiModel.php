<?php

namespace Modules\Api\Models;
use Modules\Common\Models\BaseModel;

class BaseApiModel extends BaseModel
{
    /**
     * 移除黑名单上的记录
     * @param $query
     * @return void
     */
    public function scopeRemoveBlack($query)
    {
        $userId = auth('user')->id();
        if ($userId) {
            $userBlackIds = UserBlacklist::query()->where('user_id', $userId)->pluck('to_userid');
            $query->whereNotIn('user_id', $userBlackIds);
        }

    }

    /**
     * 是否点赞
     * @param $query
     * @return void
     */
    public function scopeIsFollow($query)
    {
        $userId = auth('user')->id();
        if ($userId) {
            $query->withCount(['follow as isFollow' => function ($query2) use($userId) {
                $query2->where('user_id', $userId);
            }]);
        }
    }
}
