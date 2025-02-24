<?php

namespace Modules\Api\Models;

class Invitation extends BaseApiModel
{
    /**
     * 关联邀请用户信息
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'to_userid');
    }
}
