<?php

namespace Modules\Admin\Models;

class AuthArea extends BaseApiModel
{
	/**
	 * @name 更新时间为null时返回
	 * @param value int  $value
	 * @return Boolean
	 **/
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }

}
