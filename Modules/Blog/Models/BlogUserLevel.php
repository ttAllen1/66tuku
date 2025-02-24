<?php
/**
 * @Name 用户级别规则模型
 * @Description
 */

namespace Modules\Blog\Models;


class BlogUserLevel extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
     * @method  GET
     * @param int  $value
     * @return String
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }
    /**
     * @name 关联图片
     * @description
     * @return JSON
     **/
    public function image_one()
    {
        return $this->belongsTo('Modules\Admin\Models\AuthImage','image_id','id');
    }
}
