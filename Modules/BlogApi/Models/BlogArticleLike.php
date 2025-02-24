<?php
/**
 * @Name 文章点赞模型
 * @Description
 */

namespace Modules\BlogApi\Models;


class BlogArticleLike extends BaseApiModel
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
    /**
     * @name  关联文章表   多对一
     * @description
     **/
    public function article_to()
    {
        return $this->belongsTo('Modules\BlogApi\Models\BlogArticle','article_id','id');
    }
    /**
     * @name  关联用户表   多对一
     * @description
     **/
    public function user_to()
    {
        return $this->belongsTo('Modules\BlogApi\Models\BlogUserInfo','user_id','id');
    }
}
