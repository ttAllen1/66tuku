<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Common\Models\UserPlatQuota;
use Modules\Common\Services\BaseService;

class User extends BaseApiModel
{
    protected $guarded = [];
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

    public function getAvatarAttribute($value)
    {
        return $value ? (Str::startsWith($value, 'http') ? $value : (new BaseService())->getHttp().'/'.$value) : '';
    }
    /**
     * @name 隐藏密码
     * @description
     **/
    protected $hidden = [
        'password'
    ];

    /** 用户关联vip等级
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function vip()
    {
        return $this->hasOne(Vip::class, 'id', 'level_id');
    }

    /**
     * 用户 | 分组   多对多
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'user_groups', 'user_id', 'group_id')->withTimestamps();
    }

    /**
     * 用户禁言
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function mushin()
    {
        return $this->hasOne(UserMushin::class, 'user_id', 'id');
    }

    public function msgs()
    {
        return $this->hasMany(UserMessage::class, 'user_id', 'id');
    }

    /**
     * 用户平台额度列表
     * @return HasMany
     */
    public function plat_quotas(): HasMany
    {
        return $this->hasMany(UserPlatQuota::class, 'user_id', 'id');
    }

}
