<?php
/**
 * @Name 图片前缀地址控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Services\config\ImgPrefixService;

class ImgPrefixController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index()
    {

        return (new ImgPrefixService())->index();
    }

    public function update(Request $request)
    {
        return (new ImgPrefixService())->update($request->all());
    }

}
