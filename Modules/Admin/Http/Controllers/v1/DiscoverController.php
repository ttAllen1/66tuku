<?php
/**
 * 发现控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\discover\DiscoverService;
use Modules\Admin\Services\picture\PictureService;
use Modules\Common\Controllers\BaseController;

class DiscoverController extends BaseController
{
    /**
     * 发现列表数据
     * @description
     * @method  GET
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function discover_list(CommonPageRequest $request): JsonResponse
    {
        return (new DiscoverService())->discover_list($request->all());
    }

    public function discover_update_status(Request $request): ?JsonResponse
    {
        return (new DiscoverService())->discover_update_status($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type', 'images']));
    }

    //  详情编辑
    public function discover_update(Request $request): ?JsonResponse
    {
        return (new DiscoverService())->discover_update($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type', 'images']));
    }

    //  新增
    public function discover_create(Request $request): JsonResponse
    {
        return (new DiscoverService())->discover_create($request->all());
    }

    public function discover_delete(Request $request): ?JsonResponse
    {
        return (new DiscoverService())->discover_delete($request->input('id'));
    }

}
