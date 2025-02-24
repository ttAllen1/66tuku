<?php
namespace Modules\Admin\Models;

use Modules\Api\Models\YearPic;

class IndexPic extends BaseApiModel
{
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

    public function picOther()
    {
        return $this->hasOne(YearPic::class, 'pictureTypeId', 'pictureTypeId')->where('year', date('Y'));
    }
}
