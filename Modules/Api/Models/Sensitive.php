<?php
namespace Modules\Api\Models;

use Modules\Admin\Models\BaseApiModel;
use Modules\Admin\Models\UserMessage;

class Sensitive extends BaseApiModel
{
    protected $primaryKey = 'id';

    protected $guarded = ['user_id'];

    public function user_msg()
    {
        return $this->hasMany(UserMessage::class, 'msg_id', 'id');
    }
}
