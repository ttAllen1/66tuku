<?php
/**
 * @Name  管理员控制器
 * @Description
 * @Auther 西安咪乐多软件
 * @Date 2021/6/12 02:46
 */

namespace Modules\Admin\Http\Controllers\v1;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\AdminCreateRequest;
use Modules\Admin\Http\Requests\AdminUpdateRequest;
use Modules\Admin\Http\Requests\CommonIdRequest;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\CommonStatusRequest;
use Modules\Admin\Services\admin\AdminService;
use Modules\Admin\Services\group\GroupService;
use Modules\Admin\Services\project\ProjectService;

class AdminController extends BaseApiController
{
    /**
     * @name 管理员列表
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页显示条数
     * @param  username String 账号
     * @param  group_id Int 权限组ID
     * @param  project_id int 项目ID
     * @param  status Int 状态:0=禁用,1=启用
     * @param  created_at Array 创建时间
     * @param  updated_at Array 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new AdminService())->getList($request->only([
            'page',
            'limit',
            'name',
            'username',
            'group_id',
            'project_id',
            'status',
            'created_at',
            'updated_at',
        ]));
    }
    /**
     * @name 获取权限组
     * @description
     * @method  GET
     * @return JSON
     **/
    public function getGroupList()
    {
        return (new GroupService())->getGroupList();
    }
    /**
     * @name 获取项目列表
     * @description
     * @method  GET
     * @return JSON
     **/
    public function getProjectList()
    {
        return (new ProjectService())->getProjectList();
    }
    /**
     * @name 添加
     * @description
     * @method  POST
     * @param  username String 账号
     * @param  phone String 手机号
     * @param  username String 账号
     * @param  password String 密码
     * @param  password_confirmation String 确认密码
     * @param  group_id int 权限组ID
     * @param  project_id int 项目ID
     * @return JSON
     **/
    public function store(AdminCreateRequest $request)
    {
        return (new AdminService())->store($request->only([
            'name',
            'phone',
            'username',
            'password',
            'group_id',
            'project_id'
        ]));
    }
    /**
     * @name 修改页面
     * @description
     * @method  GET
     * @param  id Int 管理员id
     * @return JSON
     **/
    public function edit(CommonIdRequest $request)
    {
        return (new AdminService())->edit($request->get('id'));
    }
    /**
     * @name 修改提交
     * @description
     * @method  PUT
     * @param  id Int 管理员id
     * @param  name String 名称
     * @param  phone String 手机号
     * @param  username String 账号
     * @param  group_id Int 权限组ID
     * @param  project_id int 项目ID
     * @return JSON
     **/
    public function update(AdminUpdateRequest $request)
    {
        return (new AdminService())->update($request->get('id'),$request->only(['username','group_id','phone','name','project_id', 'password']));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int 管理员id
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new AdminService())->status($request->get('id'),$request->only(['status']));
    }
    /**
     * @name 初始化密码
     * @description
     * @method  PUT
     * @param  id Int 管理员id
     * @return JSON
     **/
    public function updatePwd(CommonIdRequest $request)
    {
        return (new AdminService())->updatePwd($request->get('id'));
    }

    /**
     * 生成谷歌二维码
     * @param Request $request
     * @return JsonResponse
     */
    public function generate(Request $request): JsonResponse
    {
        return (new AdminService())->generate($request->input('id'));
    }
}
