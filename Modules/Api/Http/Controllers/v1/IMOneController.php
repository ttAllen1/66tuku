<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Services\game\IMOneService;
use Modules\Common\Exceptions\ApiException;

class IMOneController extends BaseApiController
{

    /**
     * 获取跳转链接
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function getLaunchURLHTML()
    {
        return (new IMOneService())->getLaunchURLHTML();
    }


}
