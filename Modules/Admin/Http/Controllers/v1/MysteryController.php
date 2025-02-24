<?php
/**
 * @Name 活动控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\mystery\MysteryService;
use Modules\Common\Controllers\BaseController;

class MysteryController extends BaseController
{
    /**
     * @name 玄机数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new MysteryService())->index($request->all());
    }

    public function store(Request $request)
    {
        return (new MysteryService())->store($request->except(['id']));
    }

    public function update(Request $request)
    {
        return (new MysteryService())->update($request->input('id'), $request->except(['id']));
    }

    public function delete(Request $request)
    {
        return (new MysteryService())->delete($request->input('id'));
    }
}
