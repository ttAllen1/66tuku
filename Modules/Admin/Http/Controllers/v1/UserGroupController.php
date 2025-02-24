<?php
/**
 * @Name 会员分组管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\UserGroupService;

class UserGroupController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index(CommonPageRequest $request)
    {
        return (new UserGroupService())->index($request->all());
    }

}
