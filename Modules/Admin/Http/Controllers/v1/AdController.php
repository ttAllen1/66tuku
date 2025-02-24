<?php
/**
 * @Name 广告管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\AdRequest;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\AdService;

class AdController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new AdService())->index($request->all());
    }

    public function update(AdRequest $request)
    {
        return (new AdService())->update($request->input('id', 0), $request->except(['id', 'scene', 'originalSort', 'originalAdUrl', 'urlEdit', 'edit', 'position_type']));
    }

    public function update_batch(Request $request)
    {
        return (new AdService())->update_batch($request->input('id', 0), $request->except(['id', 'scene', 'originalSort', 'originalAdUrl', 'urlEdit', 'edit', 'position_type']));
    }

    public function store(AdRequest $request)
    {
        $request->validate($request->input('scene'));
        return (new AdService())->store($request->except(['scene']));
    }

    public function delete(AdRequest $request)
    {
        return (new AdService())->delete($request->input('id', 0));
    }

}
