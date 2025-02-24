<?php
namespace Modules\Admin\Models;

class HistoryNumber extends BaseApiModel
{
    protected $casts = [
        'number_attr'   => 'array',
        'te_attr'       => 'array',
        'total_attr'    => 'array',
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
}
