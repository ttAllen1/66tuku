<?php
/**
 * @Name 广告管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Events\TestEvent3;
use Modules\Admin\Events\TestEvent1;
use Modules\Admin\Events\TestEvent2;

class LaravelController extends BaseApiController
{

    public function event()
    {
        TestEvent1::dispatch('test1');
        TestEvent2::dispatch('test2');
        TestEvent3::dispatch('test3');
    }



}
