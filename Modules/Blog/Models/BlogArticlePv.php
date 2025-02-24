<?php
/**
 * @Name 文章浏览量模型
 * @Description
 */

namespace Modules\Blog\Models;


class BlogArticlePv extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
     * @param String  $value
     * @return String
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }
}
