<?php
/**
 * @Name 活动控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\humorous\HumorousService;
use Modules\Admin\Services\report\ReportService;
use Modules\Common\Controllers\BaseController;

class ReportController extends BaseController
{
    /**
     * @name 活动配置数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new ReportService())->index($request->all());
    }

    public function update(Request $request)
    {
        return (new ReportService())->update($request->input('id'), $request->except(['id']));
    }

    public function delete(Request $request)
    {
        return (new ReportService())->delete($request->input('id'));
    }

    public function detail(Request $request)
    {
        return (new ReportService())->detail($request->input('id'));
    }
}
