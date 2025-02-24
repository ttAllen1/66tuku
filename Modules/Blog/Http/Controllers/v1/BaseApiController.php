<?php
/**
 * @Name 当前模块控制器基类
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Modules\Common\Controllers\BaseController;

class BaseApiController extends BaseController
{
    public function __construct(){
        parent::__construct();
    }
}
