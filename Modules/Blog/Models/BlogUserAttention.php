<?php
/**
 * @Name 用户关注模型
 * @Description
 */

namespace Modules\Blog\Models;


class BlogUserAttention extends BaseApiModel
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
     * @name  关联博客会员表   多对一
     * @description
     **/
    public function user_to()
    {
        return $this->belongsTo('Modules\Blog\Models\BlogUserInfo','user_id','id');
    }

    /**
     * @name  关联博客会员表   多对一
     * @description
     **/
    public function user_attention_to()
    {
        return $this->belongsTo('Modules\Blog\Models\BlogUserInfo','user_attention_id','id');
    }
}
