<?php
namespace Modules\Admin\Models;


use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Api\Models\UserImage;

class PicVideo extends BaseApiModel
{
    /**
     * 获取此发现的所有图片
     * @return MorphOne
     */
    public function images(): MorphOne
    {
        return $this->morphOne(UserImage::class, 'imageable');
    }
}
