<?php
/**
 * @Name 敏感词控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\config\SensitivesService;

class SensitivesController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new SensitivesService())->index($request->all());
    }

    public function store(Request $request)
    {
        $keyword = $request->input('keyword', '');
        $status = $request->input('status', 0);
        if (!$keyword || !$status) {
            throw new \InvalidArgumentException('参数不对');
        }

        return (new SensitivesService())->store($request->except(['id']));
    }

    public function update(Request $request)
    {
        $status = $request->input('status', 0);
        $id = $request->input('id', 0);

        if (!$status || !$id) {
            throw new \InvalidArgumentException('参数不对1');
        }
        return (new SensitivesService())->update($id, $request->except(['id']));
    }

    public function delete(Request $request)
    {
        return (new SensitivesService())->delete($request->input('id', 0));
    }
}
