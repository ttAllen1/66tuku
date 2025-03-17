<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\Storage;

class Expert extends BaseApiModel
{
    protected $casts = [
        'game_info' => 'array',
    ];
    /**
     * 获取此论坛的所有评论
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }

    /**
     * 获取此论坛的所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }

    /**
     * 获取此论坛的所有图片
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(UserImage::class, 'imageable');
    }

    /**
     * 获取此论坛的对应用户信息
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联举报
     * @return MorphMany
     */
    public function complaint()
    {
        return $this->morphMany(Complaint::class, 'complaintable');
    }
}
