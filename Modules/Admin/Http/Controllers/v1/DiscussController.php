<?php
/**
 * @Name 论坛控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\discuss\DiscussService;

class DiscussController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new DiscussService())->index($request->all());
    }

    public function update(Request $request)
    {
        return (new DiscussService())->update($request->input('id', 0), $request->except(['id', 'user']));
    }

    public function status(Request $request)
    {
        return (new DiscussService())->status($request->input('id', 0), $request->except(['id', 'user', 'images']));
    }

    public function delete(Request $request)
    {
        return (new DiscussService())->delete($request->input('id', 0));
    }

    public function store(Request $request)
    {
        return (new DiscussService())->store($request->all());
    }

    public function previous(Request $request)
    {
        return (new DiscussService())->previous($request->all());
    }

    /**
     * 资料设置列表
     * @return \Illuminate\Http\JsonResponse
     * @throws \Modules\Common\Exceptions\CustomException
     */
    public function list(Request $request)
    {
        return (new DiscussService())->list($request->all());
    }

    public function update_is_index(Request $request)
    {
        return (new DiscussService())->update_is_index($request->input('id'), $request->all());
    }
}
