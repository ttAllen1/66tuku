<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\GetLaunchURLHTMLRequest;
use Modules\Api\Http\Requests\PgGetListRequest;
use Modules\Api\Services\game\GameService;
use Modules\Api\Services\game\PgService;
use Modules\Common\Exceptions\ApiException;

class PgController extends BaseApiController
{
    /**
     * 第三方session验证
     * @param Request $request
     * @return array
     */
    public function verifySession(Request $request)
    {
        return (new PgService())->verifySession($request->all());
    }

    /**
     * 获取跳转链接
     * @param GetLaunchURLHTMLRequest $request
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function getLaunchURLHTML(GetLaunchURLHTMLRequest $request)
    {
        return (new PgService())->getLaunchURLHTML($request->only(['gameId', 'referer']));
    }

    /**
     * 游戏列表
     * @param PgGetListRequest $request
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function getList(PgGetListRequest $request)
    {
        return (new GameService())->getList($request->only(['type']));
    }

}
