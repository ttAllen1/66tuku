<?php
/**
 * 前端地址控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\config\H5sService;
use Modules\Admin\Services\config\IpsService;
use Modules\Common\Exceptions\ApiException;

class H5sController extends BaseApiController
{
    /**
     * 列表数据
     * @description
     **/
    public function index()
    {
        return (new H5sService())->index();
    }

    /**
     * 添加
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function store(Request $request): JsonResponse
    {
        return (new H5sService())->store($request->except(['old']));
    }

    /**
     * 修改
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function update(Request $request): JsonResponse
    {
        return (new H5sService())->update($request->all());
    }

    /**
     * 删除
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function delete(Request $request): JsonResponse
    {
        return (new H5sService())->delete($request->all());
    }

}
