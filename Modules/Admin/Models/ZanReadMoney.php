<?php
namespace Modules\Admin\Models;

use Modules\Common\Services\BaseService;

class ZanReadMoney extends BaseApiModel
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
