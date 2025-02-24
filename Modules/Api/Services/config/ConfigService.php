<?php

namespace Modules\Api\Services\config;

use Modules\Api\Models\AuthConfig;
use Modules\Api\Services\BaseApiService;

class ConfigService extends BaseApiService
{
    public function getConfigs(array $field = ['id'])
    {
        return AuthConfig::query()->first($field);
    }

    public static function getAdImgUrl()
    {
        return AuthConfig::query()->first(['ad_img_url'])->toArray()['ad_img_url'];
    }
}
