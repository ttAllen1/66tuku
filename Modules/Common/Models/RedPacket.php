<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Api\Models\BaseApiModel;
use Modules\Api\Models\User;

class RedPacket extends BaseApiModel
{
    protected $casts = [
        'valid_date'            => 'array',
        'moneys'                => 'array'
    ];

    /**
     * 拥有此红包的用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reds', 'user_id', 'red_id');
    }
}
