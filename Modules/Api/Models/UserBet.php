<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Admin\Models\BaseApiModel;
use Modules\Admin\Models\ForecastBet;

class UserBet extends BaseApiModel
{
    public static function findAvailableNo()
    {
        $prefix = date('His');
        do{
            // 随机生成 6 位的数字，并创建订单号
            $no = $prefix.Str::random(14);
            // 判断是否已经存在
            if (!static::query()->where('order_num', $no)->exists()) {
                return $no;
            }
        }while(true);
    }

    /**
     * 关联用户
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联用户
     * @return BelongsTo
     */
    public function forecast_bet(): BelongsTo
    {
        return $this->belongsTo(ForecastBet::class, 'forecast_bet_id', 'id');
    }
}
