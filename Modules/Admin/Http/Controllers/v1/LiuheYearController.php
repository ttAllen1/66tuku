<?php
/**
 * @Name 六合年份管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\LiuheYearRequest;
use Modules\Admin\Services\liuhe\YearService;

class LiuheYearController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new YearService())->index($request->all());
    }

    public function update(LiuheYearRequest $request)
    {
        $request->validate();
        return (new YearService())->update($request->input('id', 0), $request->all());
    }

    public function store(LiuheYearRequest $request)
    {
        $request->validate('create');
        return (new YearService())->store($request->except(['id']));
    }

    public function delete(LiuheYearRequest $request)
    {
        return (new YearService())->delete($request->input('id'));
    }
}
