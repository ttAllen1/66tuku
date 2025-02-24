<?php
/**
 * @Name 广告管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index()
    {
        return view('page');
    }



}
