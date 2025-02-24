<?php

namespace Modules\Api\Models;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJoinActivity extends BaseApiModel
{
    /**
     * 关联用户
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联用户领取
     * @return BelongsTo
     */
    public function user_receive(): BelongsTo
    {
        return $this->belongsTo(UserFiveReceive::class, 'user_id', 'user_id');
    }

    /**
     * 关联活动类型
     * @return BelongsTo
     */
    public function user_bliss(): BelongsTo
    {
        return $this->belongsTo(FiveBliss::class, 'five_id', 'id');
    }
}
