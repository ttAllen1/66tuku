<?php
/**
 * @Name 用户小黑屋控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\UserMushinRequest;
use Modules\Admin\Services\user\UserMushinService;

class UserMushinController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new UserMushinService())->index($request->all());
    }

    public function update(UserMushinRequest $request)
    {
        return (new UserMushinService())->update($request->input('id', 0), $request->except(['id', 'scene', 'mushin', 'user']));
    }

    public function store(UserMushinRequest $request)
    {
        $request->validate($request->input('scene', 'create'));

        return (new UserMushinService())->store($request->except(['scene']));
    }

    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id
     **/
    public function delete(Request $request)
    {
        return (new UserMushinService())->cDestroy($request->get('id'));
    }

}
