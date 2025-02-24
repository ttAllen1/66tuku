<?php

namespace Modules\Api\Services\user;

use Modules\Api\Models\UserMushin;
use Modules\Api\Services\BaseApiService;

class UserMushinService extends BaseApiService
{
    /**
     * 判断当前用户是否被禁言
     * @return bool
     */
    public function userIfMushin(): bool
    {
        return UserMushin::query()->withMushin()->exists();
    }

}
