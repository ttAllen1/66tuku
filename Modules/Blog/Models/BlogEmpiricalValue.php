<?php
/**
 * @Name 用户经验值规则模型
 * @Description
 */

namespace Modules\Blog\Models;


class BlogEmpiricalValue extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
     * @method  GET
     * @param String  $value
     * @return String
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }
}
