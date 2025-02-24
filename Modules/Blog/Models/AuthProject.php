<?php
/**
 * @Name 系统配置模型
 * @Description
 */

namespace Modules\Blog\Models;


class AuthProject extends BaseApiModel
{
    /**
     * @name   更新时间为null时返回
     * @param  int  $value
     * @return Boolean
     */
    public function getUpdatedAtAttribute($value)
    {
        return $value?$value:'';
    }
    /**
     * @name 关联站点logo图片
     * @description 多对一
     * @return JSON
     **/
    public function logo_one()
    {
        return $this->belongsTo('Modules\Admin\Models\AuthImage','logo_id','id');
    }
    /**
     * @name 站点标识
     * @description 多对一
     * @return JSON
     **/
    public function ico_one()
    {
        return $this->belongsTo('Modules\Admin\Models\AuthImage','ico_id','id');
    }
}
