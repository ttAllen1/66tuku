<?php
/**
 * @Name Ip名单控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\config\IpsService;

class IpsController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new IpsService())->index($request->all());
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(Request $request)
    {
        return (new IpsService())->store($request->except(['scene', 'originalSort', 'originalAdUrl', 'urlEdit', 'edit', 'position_type']));
    }

    public function update(Request $request)
    {
        return (new IpsService())->update($request->input('id', 0), $request->except(['id', 'scene', 'originalSort', 'originalAdUrl', 'urlEdit', 'edit', 'position_type']));
    }

    public function delete(Request $request)
    {
        return (new IpsService())->delete($request->input('id', 0));
    }

}
