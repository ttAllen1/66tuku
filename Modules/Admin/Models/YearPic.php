<?php
namespace Modules\Admin\Models;

class YearPic extends BaseApiModel
{
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
