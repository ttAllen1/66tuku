<?php

namespace Modules\Api\Utils;

use Illuminate\Support\Facades\Redis;

class RedisLock
{
    /**
     * 加锁
     * @param $key
     * @param $expire
     * @return bool
     */
    public static function lock($key, $expire = 5)
    {
        $isLock = Redis::setnx($key, time() + $expire);
        if (!$isLock) {
            if (time() > Redis::get($key)) {
                self::unLock($key);
                $isLock = Redis::setnx($key, time() + $expire);
            }
        }
        return $isLock ? true : false;
    }

    /**
     * 去锁
     * @param $key
     * @return void
     */
    public static function unLock($key)
    {
        Redis::del($key);
    }
}
