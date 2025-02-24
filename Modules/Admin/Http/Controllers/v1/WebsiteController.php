<?php
/**
 * 三方站点控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\WebsiteRequest;
use Modules\Admin\Services\config\WebsiteService;

class WebsiteController extends BaseApiController
{
    /**
     * 列表数据
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new WebsiteService())->index($request->all());
    }

    /**
     * 添加
     * @param WebsiteRequest $request
     * @return JsonResponse
     */
    public function store(WebsiteRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new WebsiteService())->store($request->all());
    }

    public function update(WebsiteRequest $request): ?JsonResponse
    {
        $request->validate('update');

        return (new WebsiteService())->update($request->input('id', 0), $request->all());
    }

    public function status(Request $request): ?JsonResponse
    {
        return (new WebsiteService())->status($request->input('id', 0), $request->only(['status']));
    }

    public function delete(Request $request)
    {
        return (new WebsiteService())->delete($request->input('id', 0));
    }

}
