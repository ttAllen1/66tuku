<?php
/**
 * @Name 系统配置控制器
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\Blog\Services\project\ProjectService;

class ProjectController extends BaseApiController
{
    /**
     * @name 编辑页面
     * @description
     * @method  GET
     * @return JSON
     **/
    public function index()
    {
        return (new ProjectService())->index();
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
    public function update(Request $request)
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
}
