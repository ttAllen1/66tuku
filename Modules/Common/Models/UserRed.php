<?php

namespace Modules\Common\Models;

use Modules\Api\Models\BaseApiModel;
use Modules\Api\Models\User;

class UserRed extends BaseApiModel
{
    public function red_info()
    {
        return $this->belongsTo(RedPacket::class, 'red_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
