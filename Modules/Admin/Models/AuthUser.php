<?php
namespace Modules\Admin\Models;

class AuthUser extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }
    /**
     * @name 隐藏密码
     * @description
     **/
    protected $hidden = [
        'password'
    ];

}
