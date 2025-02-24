<?php

namespace Modules\Api\Models;

class Humorous extends BaseApiModel
{
    protected $table = 'humorous';

    /**
     * 获取此幽默竞猜的所有投票信息
     */
    public function votes()
    {
        return $this->morphOne(Vote::class, 'voteable');
    }

    /**
     * 获取此幽默竞猜的所有评论
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function comments()
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }

    /**
     * 获取此幽默竞猜的所有收藏
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function collect()
    {
        return $this->morphOne(UserCollect::class, 'collectable');
    }

    /**
     * 获取此幽默竞猜的所有点赞
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function follow()
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }

}
