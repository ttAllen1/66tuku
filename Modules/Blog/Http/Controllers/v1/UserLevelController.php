<?php
/**
 * @Name 用户级别规则控制器
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\Blog\Http\Requests\CommonIdRequest;
use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Blog\Http\Requests\CommonSortRequest;
use Modules\Blog\Http\Requests\CommonStatusRequest;
use Modules\Blog\Services\userLevel\UserLevelService;

class UserLevelController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @param  name String 级别名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new UserLevelService())->index($request->only([
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
     * @param  name String 级别名称
     * @param  content String 规则描述
     * @param  image_id Int 规则图片id
     * @param  status Int 状态:0=禁用,1=启用
     * @param  sort Int 排序
     * @param  start_experience Int 开始经验值
     * @param  end_experience Int 结束经验值
     * @return JSON
     **/
    public function store(Request $request)
    {
        return (new UserLevelService())->store($request->only([
            'name',
            'content',
            'image_id',
            'status',
            'sort',
            'start_experience',
            'end_experience',
        ]));
    }
    /**
     * @name 编辑页面
     * @description
     * @method  GET
     * @param  id Int
     * @return JSON
     **/
    public function edit(CommonIdRequest $request)
    {
        return (new UserLevelService())->edit($request->get('id'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param  id Int 级别规则ID
     * @param  name String 级别名称
     * @param  content String 规则描述
     * @param  image_id Int 规则图片id
     * @param  status Int 状态:0=禁用,1=启用
     * @param  sort Int 排序
     * @param  start_experience Int 开始经验值
     * @param  end_experience Int 结束经验值
     * @return JSON
     **/
    public function update(Request $request)
    {
        return (new UserLevelService())->update($request->get('id'),$request->only([
            'name',
            'content',
            'image_id',
            'status',
            'sort',
            'start_experience',
            'end_experience',
        ]));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int 级别规则ID
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new UserLevelService())->status($request->get('id'),$request->only(['status']));
    }
    /**
     * @name 排序
     * @description
     * @method  PUT
     * @param  id Int 级别规则ID
     * @param  sort Int 排序
     * @return JSON
     **/
    public function sorts(CommonSortRequest $request)
    {
        return (new UserLevelService())->sorts($request->get('id'),$request->only([
            'sort'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int 级别规则ID
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new UserLevelService())->cDestroy($request->get('id'));
    }
}
