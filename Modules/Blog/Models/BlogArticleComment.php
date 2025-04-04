<?php
/**
 * @Name 文章评论模型
 * @Description
 */

namespace Modules\Blog\Models;


class BlogArticleComment extends BaseApiModel
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
        return $this->belongsTo('Modules\Blog\Models\BlogArticle','article_id','id');
    }
    /**
     * @name  关联文章表   多对一
     * @description
     **/
    public function user_to()
    {
        return $this->belongsTo('Modules\Blog\Models\BlogUserInfo','user_id','id');
    }
}
