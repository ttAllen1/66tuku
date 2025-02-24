<?php
/**
 * @Name 文章管理模型
 * @Description
 */

namespace Modules\Blog\Models;


class BlogArticle extends BaseApiModel
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
    /**
     * @name 关联图片
     * @description 多对一
     * @return JSON
     **/
    public function image_one()
    {
        return $this->belongsTo('Modules\Admin\Models\AuthImage','image_id','id');
    }
    /**
     * @name 关联分类
     * @description 多对一
     * @return JSON
     **/
    public function type_to()
    {
        return $this->belongsTo('Modules\Blog\Models\BlogArticleType','type_id','id');
    }
    /**
     * @name 关联用户
     * @description 多对一
     * @return JSON
     **/
    public function user_to()
    {
        return $this->belongsTo('Modules\Blog\Models\BlogUserInfo','user_id','id');
    }
    /**
     * @name 关联评论
     * @description 一对多
     * @return JSON
     **/
    public function comment_many()
    {
        return $this->hasMany('Modules\Blog\Models\BlogArticleComment','article_id','id');
    }
    /**
     * @name 关联浏览量
     * @description 一对多
     * @return JSON
     **/
    public function pv_many()
    {
        return $this->hasMany('Modules\Blog\Models\BlogArticlePv','article_id','id');
    }
    /**
     * @name 关联点赞
     * @description 一对多
     * @return JSON
     **/
    public function like_many()
    {
        return $this->hasMany('Modules\Blog\Models\BlogArticleLike','article_id','id');
    }
    /**
     * @name 关联收藏
     * @description 一对多
     * @return JSON
     **/
    public function collect_many()
    {
        return $this->hasMany('Modules\Blog\Models\BlogArticleCollect','article_id','id');
    }
    /**
     * @name 关联标签
     * @description  多对多
     * @return JSON
     **/
    public function label_to()
    {
        return $this->belongsToMany('Modules\Blog\Models\BlogLabel', 'blog_article_labels', 'article_id', 'label_id')->withPivot(['article_id', 'label_id']);
    }
}
