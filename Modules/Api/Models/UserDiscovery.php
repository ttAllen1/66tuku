<?php
namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class UserDiscovery extends BaseApiModel
{
    /**
     * @description
     * @param $value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? strtotime($value) : '';
    }

    /**
     * 当前发布者用户信息1
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * 当前发布者用户信息2
     * @return HasOne
     */
    public function user_info(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * 获取此发现的所有图片
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(UserImage::class, 'imageable');
    }

    /**
     * 获取此发现的所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }

    /**
     * 获取此发现的所有收藏
     * @return MorphOne
     */
    public function collect(): MorphOne
    {
        return $this->morphOne(UserCollect::class, 'collectable');
    }

    /**
     * 获取此发现的所有评论
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(UserComment::class, 'commentable');
    }

}
