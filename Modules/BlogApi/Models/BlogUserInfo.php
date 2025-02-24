<?php
/**
 * @Name 平台用户模型
 * @Description
 */

namespace Modules\BlogApi\Models;


class BlogUserInfo extends BaseApiModel
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
     * @name  关联平台会员表   多对一
     * @description
     **/
    public function user_to()
    {
        return $this->belongsTo('Modules\Admin\Models\AuthUser','user_id','id');
    }
}
