<?php
/**
 * @Name 用户经验值规则控制器
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\Blog\Http\Requests\CommonIdRequest;
use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Blog\Http\Requests\CommonSortRequest;
use Modules\Blog\Http\Requests\CommonStatusRequest;
use Modules\Blog\Services\empiricalValue\EmpiricalValueService;

class EmpiricalValueController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @param  name String 规则名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new EmpiricalValueService())->index($request->only([
            'page',
            'limit',
            'name',
            'status',
            'created_at',
            'updated_at'
        ]));
    }
    /**
     * @name 添加
     * @description
     * @method  POST
     * @param  name String 规则名称
     * @param  content String 规则描述
     * @param  status Int 状态:0=禁用,1=启用
     * @param  sort Int 排序
     * @param  value Int 获取经验值
     * @param  restrict_value Int 限制经验值，以天为单位，0表示没有限制
     * @return JSON
     **/
    public function store(Request $request)
    {
        return (new EmpiricalValueService())->store($request->only([
            'name',
            'content',
            'status',
            'sort',
            'value',
            'restrict_value',
        ]));
    }
    /**
     * @name 编辑页面
     * @description
     * @method  GET
     * @param  id Int 经验值规则ID
     * @return JSON
     **/
    public function edit(CommonIdRequest $request)
    {
        return (new EmpiricalValueService())->edit($request->get('id'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param  id Int 经验值规则ID
     * @param  name String 规则名称
     * @param  content String 规则描述
     * @param  status Int 状态:0=禁用,1=启用
     * @param  sort Int 排序
     * @param  value Int 获取经验值
     * @param  restrict_value Int 限制经验值，以天为单位，0表示没有限制
     * @return JSON
     **/
    public function update(Request $request)
    {
        return (new EmpiricalValueService())->update($request->get('id'),$request->only([
            'name',
            'content',
            'status',
            'sort',
            'value',
            'restrict_value',
        ]));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int 经验值规则ID
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new EmpiricalValueService())->status($request->get('id'),$request->only(['status']));
    }
    /**
     * @name 排序
     * @description
     * @method  PUT
     * @param  id Int 经验值规则ID
     * @param  sort Int 排序
     * @return JSON
     **/
    public function sorts(CommonSortRequest $request)
    {
        return (new EmpiricalValueService())->sorts($request->get('id'),$request->only([
            'sort'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int 经验值规则ID
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new EmpiricalValueService())->cDestroy($request->get('id'));
    }
}
