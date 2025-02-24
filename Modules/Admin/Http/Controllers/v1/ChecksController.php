<?php
/**
 * @Name 审核控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\config\ChecksService;

class ChecksController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new ChecksService())->index($request->all());
    }

    public function store(Request $request)
    {
        return (new ChecksService())->update($request->input('id', 0), $request->except(['scene', 'originalSort', 'originalAdUrl', 'urlEdit', 'edit', 'position_type']));
    }

    public function update(Request $request)
    {
        return (new ChecksService())->update($request->input('id', 0), $request->except(['scene', 'originalSort', 'originalAdUrl', 'urlEdit', 'edit', 'position_type']));
    }

}
