<?php

namespace Modules\Api\Models;

class UserGame extends BaseApiModel
{
    /**
     * 获取最后充值平台
     * @param $user_id
     * @return mixed
     */
    public static function lastRechargeType($user_id)
    {
        return self::query()->where('user_id', $user_id)->value('last_recharge_type');
    }
}
