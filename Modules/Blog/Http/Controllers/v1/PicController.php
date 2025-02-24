<?php
/**
 * @Name 图片管理规则控制器
 * @Description
 * @Auther 西安咪乐多软件
 * @Date 2021/6/28 13:13
 */

namespace Modules\Blog\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\Blog\Http\Requests\CommonIdRequest;
use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Blog\Http\Requests\CommonSortRequest;
use Modules\Blog\Http\Requests\CommonStatusRequest;
use Modules\Blog\Services\pic\PicService;

class PicController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @param  status Int 状态:0=禁用,1=启用
     * @param  type Int 类型:0=首页轮播图
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new PicService())->index($request->only([
            'page',
            'limit',
            'type',
            'status',
            'created_at',
            'updated_at'
        ]));
    }
    /**
     * @name 添加
     * @description
     * @method  POST
     * @param  content String 图片描述
     * @param  url String 跳转地址
     * @param  image_id Int 规则图片id
     * @param  status Int 状态:0=禁用,1=启用
     * @param  type Int 类型:0=首页轮播图
     * @param  sort Int 排序
     * @return JSON
     **/
    public function store(Request $request)
    {
        return (new PicService())->store($request->only([
            'content',
            'url',
            'image_id',
            'status',
            'sort',
            'type'
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
        return (new PicService())->edit($request->get('id'));
    }
    /**
     * @name 编辑提交
     * @description
     * @method  PUT
     * @param  id Int 级别规则ID
     * @param  content String 图片描述
     * @param  url String 跳转地址
     * @param  image_id Int 规则图片id
     * @param  status Int 状态:0=禁用,1=启用
     * @param  type Int 类型:0=首页轮播图
     * @return JSON
     **/
    public function update(Request $request)
    {
        return (new PicService())->update($request->get('id'),$request->only([
            'content',
            'url',
            'image_id',
            'status',
            'sort',
            'type'
        ]));
    }
    /**
     * @name 调整状态
     * @description
     * @method  PUT
     * @param  id Int
     * @param  status Int 状态（0或1）
     * @return JSON
     **/
    public function status(CommonStatusRequest $request)
    {
        return (new PicService())->status($request->get('id'),$request->only(['status']));
    }
    /**
     * @name 排序
     * @description
     * @method  PUT
     * @param  id Int
     * @param  sort Int 排序
     * @return JSON
     **/
    public function sorts(CommonSortRequest $request)
    {
        return (new PicService())->sorts($request->get('id'),$request->only([
            'sort'
        ]));
    }
    /**
     * @name 删除
     * @description
     * @method  DELETE
     * @param id Int
     * @return JSON
     **/
    public function cDestroy(CommonIdRequest $request)
    {
        return (new PicService())->cDestroy($request->get('id'));
    }
}
