<?php
/**
 * @Name 标签管理
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\Blog\Http\Requests\CommonIdRequest;
use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Blog\Http\Requests\CommonSortRequest;
use Modules\Blog\Http\Requests\CommonStatusRequest;
use Modules\Blog\Services\label\LabelService;

class LabelController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @param  name String 标签名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new LabelService())->index($request->only([
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
     * @param  name String 标签名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  sort Int 排序
     * @return JSON
     **/
    public function store(Request $request)
    {
        return (new LabelService())->store($request->only([
            'name',
            'status',
            'sort'
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
        return (new LabelService())->edit($request->get('id'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param  id Int 标签ID
     * @param  name String 标签名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  sort Int 排序
     * @return JSON
     **/
    public function update(Request $request)
    {
        return (new LabelService())->update($request->get('id'),$request->only([
            'name',
            'status',
            'sort'
        ]));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int 标签ID
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new LabelService())->status($request->get('id'),$request->only(['status']));
    }
    /**
     * @name 排序
     * @description
     * @method  PUT
     * @param  id Int 标签ID
     * @param  sort Int 排序
     * @return JSON
     **/
    public function sorts(CommonSortRequest $request)
    {
        return (new LabelService())->sorts($request->get('id'),$request->only([
            'sort'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int 标签ID
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new LabelService())->cDestroy($request->get('id'));
    }
}
