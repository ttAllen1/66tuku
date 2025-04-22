<?php
/**
 * 论坛控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\discuss\DiscussService;
use Modules\Common\Exceptions\CustomException;

class DiscussController extends BaseApiController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     * @description
     * @method  GET
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new DiscussService())->index($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function update(Request $request): ?JsonResponse
    {
        return (new DiscussService())->update($request->input('id', 0), $request->except(['id', 'user']));
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function status(Request $request): ?JsonResponse
    {
        return (new DiscussService())->status($request->input('id', 0), $request->except(['id', 'user', 'images']));
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function delete(Request $request): ?JsonResponse
    {
        return (new DiscussService())->delete($request->input('id', 0));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        return (new DiscussService())->store($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function previous(Request $request): JsonResponse
    {
        return (new DiscussService())->previous($request->all());
    }

    /**
     * 资料设置列表
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        return (new DiscussService())->list($request->all());
    }

    public function update_is_index(Request $request): ?JsonResponse
    {
        return (new DiscussService())->update_is_index($request->input('id'), $request->all());
    }


}
