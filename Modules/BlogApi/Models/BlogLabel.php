<?php
/**
 * @Name 标签管理模型
 * @Description
 */

namespace Modules\BlogApi\Models;


class BlogLabel extends BaseApiModel
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
