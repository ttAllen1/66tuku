<?php

namespace Modules\Admin\Models;

class ForecastBet extends BaseApiModel
{
    protected $casts = [
//        'contents'  => 'array'
    ];

    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? $value : '';
    }

    public function pName()
    {
        return $this->hasOne(self::class, 'id', 'pid');
    }

    public function subList()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }

}
