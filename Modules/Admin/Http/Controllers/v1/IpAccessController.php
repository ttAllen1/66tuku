<?php
/**
 * 三方站点控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\config\IpAccessService;

class IpAccessController extends BaseApiController
{
    /**
     * 列表数据
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new IpAccessService())->index($request->all());
    }

    /**
     * 失效域名
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function invalid_domains(CommonPageRequest $request): JsonResponse
    {
        return (new IpAccessService())->invalid_domains($request->all());
    }
}
