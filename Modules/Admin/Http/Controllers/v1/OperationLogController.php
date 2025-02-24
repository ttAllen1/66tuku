<?php
/**
 * @Name 操作日志控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\CommonIdArrRequest;
use Modules\Admin\Http\Requests\CommonIdRequest;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\log\OperationLogService;

class OperationLogController extends BaseApiController
{
    /**
     * @name 管理员列表
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页显示条数
     * @param  url String 操作路由
     * @param  method String 请求方式
     * @param  username String 管理员账号
     * @param  created_at Array 创建时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new OperationLogService())->index($request->only([
            'page',
            'limit',
            'url',
            'username',
            'created_at',
            'method'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int id
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new OperationLogService())->cDestroy($request->get('id'));
    }
    /**
     * @name 批量删除
     * @description
     * @method  DELETE
     * @param idArr Array id数组
     * @return JSON
     **/
    public function cDestroyAll(CommonIdArrRequest $request)
    {
        return (new OperationLogService())->cDestroyAll($request->get('idArr'));
    }

}
