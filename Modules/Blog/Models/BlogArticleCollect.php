<?php
/**
 * @Name 文章收藏模型
 * @Description
 * @Auther 西安咪乐多软件
 * @Date 2021/7/2 15:57
 */

namespace Modules\Blog\Models;


class BlogArticleCollect extends BaseApiModel
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
