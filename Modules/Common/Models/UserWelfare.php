<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admin\Models\User;
use Modules\Api\Models\BaseApiModel;
use Modules\Api\Models\StationMsg;

class UserWelfare extends BaseApiModel
{
    protected $casts = [
        'random_money'          => 'array',
        'really_random_money'   => 'array',
        'valid_receive_date'    => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     *  关联站内信
     * @return BelongsTo
     */
    public function msg(): BelongsTo
    {
        return $this->belongsTo(StationMsg::class, 'send_msg_id', 'id');
    }
}
