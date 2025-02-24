<?php

namespace Modules\Admin\Models;

use Modules\Common\Services\BaseService;

class UserChat extends BaseApiModel
{
    protected $casts = [
        'from'  => 'array',
        'to'    => 'array',
    ];

    protected $appends = ['image'];

    public function getImageAttribute()
    {
        if ($this->style == 'image') {
//            return $this->message ? ((strpos($this->message, 'http') === 0) ? $this->message : ((new BaseService())->getHttp().'/'.$this->message)) : '';
            return $this->message ? ((strpos($this->message, 'http') === 0) ? $this->message : ('https://api1.49tkapi8.com/'.$this->message)) : '';
        }
        return '';
    }

    public function room()
    {
        return $this->hasOne(ChatRoom::class, 'id', 'room_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'from_user_id');
    }
}
