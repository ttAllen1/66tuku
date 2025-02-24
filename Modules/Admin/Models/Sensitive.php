<?php
namespace Modules\Admin\Models;

class Sensitive extends BaseApiModel
{
    protected $primaryKey = 'id';

    protected $guarded = ['user_id'];

    public function user_msg()
    {
        return $this->hasMany(UserMessage::class, 'msg_id', 'id');
    }
}
