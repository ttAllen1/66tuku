<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Api\Models\BaseApiModel;
use Modules\Api\Models\Complaint;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\User;
use Modules\Api\Models\UserComment;
use Modules\Api\Models\UserFollow;

class PicDiagram extends BaseApiModel
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(PicDetail::class, 'pic_detail_id', 'id');
    }

    /**
     * 获取此图解的所有评论
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }

    /**
     * 获取此图解的所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
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
