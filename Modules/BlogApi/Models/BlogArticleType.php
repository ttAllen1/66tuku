<?php
/**
 * @Name 文章分类模型
 * @Description
 */

namespace Modules\BlogApi\Models;



class BlogArticleType extends BaseApiModel
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
