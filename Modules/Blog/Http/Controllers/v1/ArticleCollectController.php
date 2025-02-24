<?php
/**
 * @Name 文章收藏控制器
 * @Description
 */

namespace Modules\Blog\Http\Controllers\v1;


use Modules\Blog\Http\Requests\CommonPageRequest;
use Modules\Blog\Services\articleCollect\ArticleCollectService;

class ArticleCollectController extends BaseApiController
{
    /**
     * @name 列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @param  nickname String 昵称
     * @param  title String 文章标题
     * @param  created_at String 创建时间
     * @param  updated_at String 更新时间
     * @return JSON
     **/
    public function index(CommonPageRequest $request)
    {
        return (new ArticleCollectService())->index($request->only([
            'page',
            'limit',
            'nickname',
            'title',
            'created_at',
            'updated_at'
        ]));
    }
}
