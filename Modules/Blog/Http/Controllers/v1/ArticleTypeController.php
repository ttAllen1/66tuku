<?php
/**
 * @Name 文章分类控制器
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPidRequest;
use Modules\Blog\Http\Requests\CommonIdRequest;
use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Blog\Http\Requests\CommonSortRequest;
use Modules\Blog\Http\Requests\CommonStatusRequest;
use Modules\Blog\Services\articleType\ArticleTypeService;

class ArticleTypeController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  name String 标签名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(Request $request)
    {
        return (new ArticleTypeService())->index($request->only([
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
     * @param  name String 分类名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  pid Int 父级id
     * @param  sort Int 排序
     * @return JSON
     **/
    public function store(Request $request)
    {
        return (new ArticleTypeService())->store($request->only([
            'name',
            'status',
            'pid',
            'sort',
			'level'
        ]));
    }
    /**
     * @name 添加子级返回父级id
     * @description
     * @method  GET
     * @param  pid Int 父级id
     * @return JSON
     **/
    public function pidArr(CommonPidRequest $request){
        return (new ArticleTypeService())->pidArr($request->get('pid'));
    }
    /**
     * @name 编辑页面
     * @description
     * @method  GET
     * @param  id Int 文章分类ID
     * @return JSON
     **/
    public function edit(CommonIdRequest $request)
    {
        return (new ArticleTypeService())->edit($request->get('id'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param  id Int 标签ID
     * @param  name String 分类名称
     * @param  status Int 状态:0=禁用,1=启用
     * @param  pid Int 父级id
     * @param  sort Int 排序
     * @return JSON
     **/
    public function update(Request $request)
    {
        return (new ArticleTypeService())->update($request->get('id'),$request->only([
            'name',
            'status',
            'pid',
            'sort',
			'level'
        ]));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int 文章分类ID
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new ArticleTypeService())->status($request->get('id'),$request->only(['status']));
    }
    /**
     * @name 排序
     * @description
     * @method  PUT
     * @param  id Int 文章分类ID
     * @param  sort Int 排序
     * @return JSON
     **/
    public function sorts(CommonSortRequest $request)
    {
        return (new ArticleTypeService())->sorts($request->get('id'),$request->only([
            'sort'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int 文章分类ID
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new ArticleTypeService())->cDestroy($request->get('id'));
    }
}
