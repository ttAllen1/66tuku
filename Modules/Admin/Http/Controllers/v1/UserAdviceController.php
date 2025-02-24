<?php
/**
 * @Name 用户意见控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\UserAdviceRequest;
use Modules\Admin\Services\user\UserAdviceService;

class UserAdviceController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {

        return (new UserAdviceService())->index($request->all());
    }

    public function update(UserAdviceRequest $request)
    {
        $request->validate('reply');

        return (new UserAdviceService())->update($request->input('id', 0), $request->except(['scene']));
    }

    public function delete(Request $request)
    {
        return (new UserAdviceService())->delete($request->input('id'));
    }
}
