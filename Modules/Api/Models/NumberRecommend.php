<?php

namespace Modules\Api\Models;

use Modules\Admin\Models\BaseApiModel;

class NumberRecommend extends BaseApiModel
{
    protected $guarded = [];

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
