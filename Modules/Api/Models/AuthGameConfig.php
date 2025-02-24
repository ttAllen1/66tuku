<?php

namespace Modules\Api\Models;

use Illuminate\Support\Facades\Redis;

class AuthGameConfig extends BaseApiModel
{
    /**
     * 活动配置参数获取
     * @param $k
     * @return array|false|mixed|null
     */
    static public function val($k)
    {
        $data = [];
        $noCacheKey = [];
        if (!is_array($k)) {
            if (empty($k)) return false;
            $k = explode(',', $k);
        }
        foreach ($k as $value) {
            $cacheValue = Redis::get('auth_game_config_' . $value);
            if ($cacheValue) {
                $data[$value] = $cacheValue;
            } else {
                $noCacheKey[] = $value;
            }
        }
        if (count($noCacheKey) > 0) {
            $result = self::query()->whereIn('k', $noCacheKey)->get()->toArray();
            if (!$result) {
                return false;
            }
            foreach ($result as $item) {
                Redis::setex('auth_game_config_' . $item['k'], 3600, $item['v']);
                $data[$item['k']] = $item['v'];
            }
        }
        if (count($data) == 1) {
            return array_shift($data);
        }
        return $data;
    }
}
