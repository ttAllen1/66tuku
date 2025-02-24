<?php

namespace Modules\Api\Models;

class UserAdvice extends BaseApiModel
{
    /**
     * 获取此意见得所有图片
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function images()
    {
        return $this->morphMany(UserImage::class, 'imageable');
    }

}
