<?php

namespace Modules\Admin\Models;

class LiuheOpenDay extends BaseApiModel
{
    protected $fillable = ['lotteryType', 'year', 'month', 'open_date'];
    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? strtotime($value) : '';
    }
}
