<?php

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admin\Models\User;
use Modules\Admin\Models\ZanReadMoney;
use Modules\Api\Models\BaseApiModel;
use Modules\Api\Models\UserBet;
use Modules\Api\Models\UserReward;

class UserGoldRecord extends BaseApiModel
{
    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function bet(): HasOne
    {
        return $this->hasOne(UserBet::class, 'id', 'user_bet_id');
    }

    /**
     * @return HasOne
     */
    public function welfare(): HasOne
    {
        return $this->hasOne(UserWelfare::class, 'id', 'user_welfare_id');
    }

    /**
     * @return HasOne
     */
    public function reward(): HasOne
    {
        return $this->hasOne(UserReward::class, 'id', 'user_reward_id');
    }

    /**
     * @return HasOne
     */
    public function posts(): HasOne
    {
        return $this->hasOne(ZanReadMoney::class, 'id', 'user_post_id');
    }
}
