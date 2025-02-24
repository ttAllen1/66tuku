<?php
/**
 * @Name 站内信息控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\AnnounceRequest;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\config\AnnounceService;

class AnnounceController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new AnnounceService())->index($request->all());
    }

    public function store(AnnounceRequest $request)
    {
//        $request->validate($request->input());

        return (new AnnounceService())->store($request->except(['scene']));
    }

    public function update(AnnounceRequest $request)
    {
//        $request->validate();
        return (new AnnounceService())->update($request->input('id', 0), $request->except(['id', 'scene', 'user_msg']));
    }

    public function delete(AnnounceRequest $request)
    {
        return (new AnnounceService())->delete($request->input('id', 0));
    }
}
