<?php

/**
 * @Name 博客首页相关接口
 * @Description
 */

namespace Modules\BlogApi\Http\Controllers\v1;


use Illuminate\Http\Request;
use Modules\BlogApi\Http\Requests\CommonPageRequest;
use Modules\BlogApi\Services\article\ArticleService;
use Modules\BlogApi\Services\articleType\ArticleTypeService;
use Modules\BlogApi\Services\pic\PicService;

class IndexController extends BaseApiController
{
    /**
     * @name
     * @description
     * @method  GET
     * @param
     * @return JSON
     **/
    public function typeList(){
        return (new ArticleTypeService())->typeList();
    }
    /**
     * @name 图片列表
     * @description
     * @method  GET
     * @param  type  Int  类型:0=首页轮播图
     * @return JSON
     **/
    public function bannerList(Request $request){
        return (new PicService)->bannerList($request->get('type'));
    }

    /**
     * @name 文章列表
     * @description
     * @method  GET
     * @param  id  Int 文章分类  一级id
     * @param  type_id INt  文章分类二级id
     * @param  title  String   文章标题模糊查询
     * @param  page Int 页码
     * @param  limit Int 每页条数
     * @return JSON
     **/
    public function articleList(CommonPageRequest $request){
        return (new ArticleService())->articleList($request->only([
            'id',
            'type_id',
            'title',
            'page',
            'limit'
        ]));
    }

    /**
     * @name 推荐文章列表
     * @description
     * @method  GET
     * @return JSON
     **/
    public function openArticleList(){
        return (new ArticleService())->openArticleList();
    }
}
