<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Api\Events\PicDetailCreatedEvent;

class PicDetail extends BaseApiModel
{
    protected $fillable = [];

    protected $dispatchesEvents = [
//        'created' => PicDetailCreatedEvent ::class,
    ];

    /**
     * 获取此图片的所有投票信息
     */
    public function votes(): MorphOne
    {
        return $this->morphOne(Vote::class, 'voteable');
    }

    /**
     * 获取此图片得所有评论
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }

    /**
     * 获取此图片得所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }

    /**
     * 获取此图片得所有收藏
     * @return MorphOne
     */
    public function collect(): MorphOne
    {
        return $this->morphOne(UserCollect::class, 'collectable');
    }

    /**
     * 图解
     * @return HasMany
     */
    public function diagram(): HasMany
    {
        return $this->hasMany(PicDiagram::class, 'pic_detail_id', 'id');
    }
}
