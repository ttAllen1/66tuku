<?php
/**
 * @Name 禁言理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\MushinRequest;
use Modules\Admin\Services\user\MushinService;

class MushinController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new MushinService())->index($request->all());
    }

    public function update(MushinRequest $request)
    {
        $request->validate($request->input('scene', ''));
        return (new MushinService())->update($request->input('id', 0), $request->except(['scene']));
    }

    public function store(MushinRequest $request)
    {
        $request->validate($request->input('scene'));
        return (new MushinService())->store($request->except(['scene']));
    }

}
