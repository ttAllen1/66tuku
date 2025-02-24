<?php

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\UserCheckService;
use Modules\Common\Controllers\BaseController;

class UserCheckController extends BaseController
{
    /**
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function avatar(CommonPageRequest $request)
    {
        return (new UserCheckService())->avatar_list($request->all());
    }

    public function update(Request $request)
    {
        return (new UserCheckService())->update($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type', 'time_str', 'images']));
    }

}
