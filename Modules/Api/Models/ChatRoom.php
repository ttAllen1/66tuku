<?php

namespace Modules\Api\Models;

class ChatRoom extends BaseApiModel
{
    public function getUpdatedAtAttribute($value)
    {
        return $value ? strtotime($value) : '';
    }
}
