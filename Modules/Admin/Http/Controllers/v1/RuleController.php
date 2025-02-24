<?php
/**
 * @Name 权限菜单管理
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\AuthOpenRequest;
use Modules\Admin\Http\Requests\CommonIdRequest;
use Modules\Admin\Http\Requests\CommonPidRequest;
use Modules\Admin\Http\Requests\CommonSortRequest;
use Modules\Admin\Http\Requests\CommonStatusRequest;
use Modules\Admin\Services\rule\RuleService;

class RuleController extends BaseApiController
{
    /**
     * @name 权限列表
     * @description
     * @method  GET
     * @return JSON
     **/
    public function index()
    {
        return (new RuleService())->index();
    }
    /**
     * @name 添加
     * @description
     * @method  POST
     * @param  pid Int 父级ID
     * @param  path String 标识
     * @param  url String 路由文件
     * @param  redirect String 重定向路径
     * @param  name String 权限名称
     * @param  type Int 菜单类型:1=模块,2=目录,3=菜单
     * @param  status Int 侧边栏显示状态:0=隐藏,1=显示
     * @param  auth_open Int 是否验证权限:0=否,1=是
     * @param  level Int 级别
     * @param  affix Int 是否固定面板:0=否,1=是
     * @param  icon String 图标名称
     * @param  sort Int 排序
     * @return JSON
     **/
    public function store(Request $request)
    {
        return (new RuleService())->store($request->only([
            'pid',
            'path',
            'url',
            'redirect',
            'name',
            'type',
            'status',
            'auth_open',
            'level',
            'affix',
            'icon',
            'sort',
        ]));
    }
    /**
     * @name 编辑页面
     * @description
     * @method  GET
     * @param  id Int 菜单ID
     * @return JSON
     **/
    public function edit(CommonIdRequest $request)
    {
        return (new RuleService())->edit($request->get('id'));
    }
    /**
     * @name 添加子级返回父级id
     * @description
     * @method  GET
     * @param  pid Int 父级id
     * @return JSON
     **/
    public function pidArr(CommonPidRequest $request){
        return (new RuleService())->pidArr($request->get('pid'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param id Int 菜单ID
     * @param  pid Int 父级ID
     * @param  path String 标识
     * @param  url String 路由文件
     * @param  redirect String 重定向路径
     * @param  name String 权限名称
     * @param  type Int 菜单类型:1=模块,2=目录,3=菜单
     * @param  status Int 侧边栏显示状态:0=隐藏,1=显示
     * @param  auth_open Int 是否验证权限:0=否,1=是
     * @param  level Int 级别
     * @param  affix Int 是否固定面板:0=否,1=是
     * @param  icon String 图标名称
     * @param  sort Int 排序
     * @return JSON
     **/
    public function update(Request $request)
    {
        return (new RuleService())->update($request->get('id'),$request->only([
            'pid',
            'path',
            'url',
            'redirect',
            'name',
            'type',
            'status',
            'auth_open',
            'level',
            'affix',
            'icon',
            'sort',
        ]));
    }
    /**
     * @name 菜单状态
     * @description
     * @method  PUT
     * @param  id Int 菜单ID
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new RuleService())->status($request->get('id'),$request->only([
            'status'
        ]));
    }
    /**
     * @name 是否验证权限
     * @description
     * @method  PUT
     * @param  id int 菜单ID
     * @param  auth_open int 状态（0或1）
     * @return JSON
     **/
    public function open(AuthOpenRequest $request)
    {
        $data = $request->all();
        return (new RuleService())->open($request->get('id'),$request->only([
            'auth_open'
        ]));
    }
    /**
     * @name 排序
     * @description
     * @method  PUT
     * @param  id Int 菜单ID
     * @param  sort Int 排序
     * @return JSON
     **/
    public function sorts(CommonSortRequest $request)
    {
        return (new RuleService())->sorts($request->get('id'),$request->only([
            'sort'
        ]));
    }
    /**
     * @name 固定面板
     * @description
     * @method  PUT
     * @param  id Int 菜单ID
     * @param  affix Int 是否固定面板:0=否,1=是
     * @return JSON
     **/
    public function affix(Request $request)
    {
        return (new RuleService())->affix($request->get('id'),$request->only([
            'affix'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int 菜单ID
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new RuleService())->cDestroy($request->get('id'));
    }
}
