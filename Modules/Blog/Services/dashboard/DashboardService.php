<?php
/**
 * @Name
 * @Description
 */

namespace Modules\Blog\Services\dashboard;


use Modules\Blog\Models\AuthProject;
use Modules\Blog\Models\BlogArticle;
use Modules\Blog\Models\BlogArticleLike;
use Modules\Blog\Models\BlogArticlePv;
use Modules\Blog\Models\BlogUserInfo;
use Modules\Blog\Services\BaseApiService;

class DashboardService extends BaseApiService
{
    public function index(){
        $list = [
            'blog_article_count'=>BlogArticle::where(['project_id'=>$this->projectId,'status'=>1])->count(),
            'auth_project_name'=>AuthProject::where(['id'=>$this->projectId])->value('name'),
            'blog_article_pv_count'=>BlogArticlePv::where(['project_id'=>$this->projectId])->count(),
            'blog_user_info_count'=>BlogUserInfo::where(['project_id'=>$this->projectId,'status'=>1])->count(),
            'blog_article_like_count'=>BlogArticleLike::where(['project_id'=>$this->projectId,'status'=>1])->count()
        ];
        return $this->apiSuccess('',$list);
    }
}
