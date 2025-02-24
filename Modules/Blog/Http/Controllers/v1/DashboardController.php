<?php
/**
 * @Name 数据看板控制器
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Modules\Blog\Services\dashboard\DashboardService;

class DashboardController extends BaseApiController
{
    public function index(){
        return (new DashboardService())->index();
    }
}
