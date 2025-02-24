<?php

namespace Modules\Api\Models;

use Modules\Common\Services\BaseService;

class Game extends BaseApiModel
{
    public function getIconAttribute($value): string
    {
        return (new BaseService())->getHttp().$value;
    }

}
