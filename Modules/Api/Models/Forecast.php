<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Admin\Models\BaseApiModel;

class Forecast extends BaseApiModel
{
    protected $casts = [
        'odds'  => 'array'
    ];
    public function subList()
    {
        return $this->hasMany(self::class, 'pid', 'id');
    }

    public function getShowListAttribute($value)
    {
        if (!$value) {
            return [];
        }

        return explode('|', $value);
    }

    /**
     * 获取此竞猜的所有点赞
     * @return MorphOne
     */
    public function follow(): MorphOne
    {
        return $this->morphOne(UserFollow::class, 'followable');
    }

}
