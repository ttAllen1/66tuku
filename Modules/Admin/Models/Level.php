<?php
namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Common\Services\BaseService;

class Level extends BaseApiModel
{
    /**
     * @name 更新时间为null时返回
     * @description
     * @param value String  $value
     * @return Boolean
     **/
    public function getUpdatedAtAttribute($value)
    {
        return $value ? strtotime($value) : '';
    }

    public function getImgUrlAttribute($value)
    {
        return $value = $value ? (strpos($value, 'http') ===0 ? $value : (new BaseService())->getHttp().'/'.$value) : '';
    }

    /**
     * 获取此等级得所有图片
     * @return MorphOne
     */
    public function images()
    {
        return $this->morphOne(UserImages::class, 'imageable');
    }
}
