<?php

namespace Modules\Api\Models;

use Illuminate\Support\Str;
use Modules\Common\Services\BaseService;

class Ad extends BaseApiModel
{
    // 上架 且 为限时上架时未过期得数据
    public function scopeActive($query)
    {
        $today = date('Y-m-d');
        return $query
                ->where('status', 1)
                ->where(function($query) use ($today) {
                    $query->where(function($query) use ($today) {
                        $query->where('open_time_expired', 1)
                            ->where('start_open_with', '<=', $today)
                            ->where('end_open_with', '>=', $today);
                    })->orwhere('open_time_expired', 2);
                });
    }

    public function getAdUrlAttribute($value)
    {
        return $value;
    }

    public function getAdImageAttribute($value)
    {
        return $value ? (Str::startsWith($value, 'http') ? $value : (new BaseService())->getHttp() . $value) : '';
    }

}
