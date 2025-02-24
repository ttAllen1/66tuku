<?php
/**
 * @Name 活动控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\humorous\HumorousService;
use Modules\Common\Controllers\BaseController;

class HumorousController extends BaseController
{
    /**
     * @name 活动配置数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new HumorousService())->index($request->all());
    }

    public function update(Request $request)
    {
        return (new HumorousService())->update($request->input('id'), $request->except(['id']));
    }

    public function delete(Request $request)
    {
        return (new HumorousService())->delete($request->input('id'));
    }

    public function store(Request $request)
    {
        return (new HumorousService())->store($request->all());
    }
}
