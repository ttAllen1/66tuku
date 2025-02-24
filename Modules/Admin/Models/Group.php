<?php
namespace Modules\Admin\Models;

class Group extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? $value : '';
    }

    /**
     * 拥有此组别的所有用户
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_groups', 'user_id', 'group_id');
    }

}
