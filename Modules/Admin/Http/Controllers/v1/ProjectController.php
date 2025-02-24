<?php
/**
 * @Name 项目管理控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Modules\Admin\Http\Requests\AuthGroupStoreRequest;
use Modules\Admin\Http\Requests\AuthGroupUpdateRequest;
use Modules\Admin\Http\Requests\CommonIdRequest;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\CommonStatusRequest;
use Modules\Admin\Services\project\ProjectService;

class ProjectController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @param  name String 权限组名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new ProjectService())->index($request->only([
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
     * @param  name String 项目名称
     * @param  url String 项目地址
     * @param  logo_id Int 站点logo
     * @param  ico_id Int 站点标识
     * @param  description String 项目描述
     * @param  keywords String 项目关键词
     * @param  status Int 状态:0=禁用,1=启用
     * @return JSON
     **/
    public function store(AuthGroupStoreRequest $request)
    {
        return (new ProjectService())->store($request->only([
            'name',
            'url',
            'logo_id',
            'ico_id',
            'description',
            'keywords',
            'status',
        ]));
    }
    /**
     * @name 编辑页面
     * @description
     * @method  GET
     * @param  id Int 项目id
     * @return JSON
     **/
    public function edit(CommonIdRequest $request)
    {
        return (new ProjectService())->edit($request->get('id'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param  id Int 项目id
     * @param  name String 项目名称
     * @param  url String 项目地址
     * @param  logo_id Int 站点logo
     * @param  ico_id Int 站点标识
     * @param  description String 项目描述
     * @param  keywords String 项目关键词
     * @param  status Int 状态:0=禁用,1=启用
     * @return JSON
     **/
    public function update(AuthGroupUpdateRequest $request)
    {
        return (new ProjectService())->update($request->get('id'),$request->only([
            'name',
            'url',
            'logo_id',
            'ico_id',
            'description',
            'keywords',
            'status',
        ]));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int 项目id
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new ProjectService())->status($request->get('id'),$request->only(['status']));
    }
}
