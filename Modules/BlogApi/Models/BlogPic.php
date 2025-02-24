<?php
/**
 * @Name 图片管理规则模型
 * @Description
 */

namespace Modules\BlogApi\Models;


class BlogPic extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
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
