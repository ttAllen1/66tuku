<?php

namespace Modules\Api\Models;

class AuthActivityConfig extends BaseApiModel
{
    /**
     * 活动配置参数获取
     * @param $k
     * @return array|false
     */
    static public function val($k)
    {
        if (!is_array($k)) {
            $k = explode(',', $k);
        }
        $result = self::query()->whereIn('k', $k)->get()->toArray();
        if (!$result) {
            return false;
        }
        if (count($k) == 1) {
            return $result[0]['v'];
        }
        $config = [];
        foreach ($result as $item) {
            $config[$item['k']] = $item['v'];
        }
        return $config;
    }
}
