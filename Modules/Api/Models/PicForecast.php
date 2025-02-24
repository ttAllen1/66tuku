<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PicForecast extends BaseApiModel
{
    protected $casts = [
        'content'   => 'array'
    ];

    /**
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(PicDetail::class, 'pic_detail_id', 'id');
    }

    /**
     * 获取此竞猜的所有评论
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }

    /**
     * 获取此竞猜的所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }
}
