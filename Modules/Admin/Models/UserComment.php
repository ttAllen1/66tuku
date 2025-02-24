<?php
namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Api\Models\UserImage;

class UserComment extends BaseApiModel
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取此评论得所有图片
     * @return MorphMany
     */
    public function images(): MorphMany
    {
        return $this->morphMany(UserImage::class, 'imageable');
    }

}
