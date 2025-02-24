<?php
/**
 * @Name 开奖日期管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\liuhe\OpenDateService;

class LotteryDateController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new OpenDateService())->index($request->all());
    }

    public function switch(Request $request)
    {
        return (new OpenDateService())->switch($request->only(['switch']));
    }

    public function update(Request $request)
    {
        return (new OpenDateService())->update($request->input('id', 0), $request->except(['id', 'user', 'images']));
    }

    public function delete(Request $request)
    {
        return (new OpenDateService())->delete($request->input('id', 0));
    }

    public function store(Request $request)
    {
        return (new OpenDateService())->store($request->except(['id']));
    }

}
