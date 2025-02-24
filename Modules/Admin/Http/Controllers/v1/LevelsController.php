<?php
/**
 * @Name 用户等级控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\config\LevelsService;

class LevelsController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new LevelsService())->index($request->all());
    }

    public function store(Request $request)
    {

        return (new LevelsService())->store($request->except(['id', 'img_id']));
    }

    public function update(Request $request)
    {
        return (new LevelsService())->update($request->input('id', 0), $request->all());
    }
}
