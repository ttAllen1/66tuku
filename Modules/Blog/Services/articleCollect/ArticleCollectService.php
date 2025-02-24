<?php
/**
 * @Name 文章收藏服务
 * @Description
 */

namespace Modules\Blog\Services\articleCollect;


use Modules\Blog\Models\BlogArticleCollect;
use Modules\Blog\Services\BaseApiService;

class ArticleCollectService extends BaseApiService
{
    /**
     * @name 列表数据
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页条数
     * @param  data.nickname String 昵称
     * @param  data.title String 文章标题
     * @param  data.created_at String 创建时间
     * @param  data.updated_at String 更新时间
     * @return JSON
     **/
    public function index(array $data){
        $model = BlogArticleCollect::query();
        $model = $this->queryCondition($model,$data,'');
        $list = $model->select('id','user_id','article_id','created_at')
            ->where(['project_id'=>$this->projectId,'status'=>1])
            ->with([
                'article_to'=>function($query){
                    $query->select('id','title');
                },
                'user_to'=>function($query){
                    $query->select('id','user_id');
                },
                'user_to.user_to'=>function($query){
                    $query->select('id','nickname','name','email');
                }
            ])
            ->whereHas('article_to',function($query)use ($data){
                if (!empty($data['title'])){
                    $query->where('title','like','%' . $data['title'] . '%');
                }
            })
            ->whereHas('user_to.user_to',function($query)use ($data){
                $query->where('status',1);
                if (!empty($data['nickname'])){
                    $query->where('nickname','like','%' . $data['nickname'] . '%');
                }
            })
            ->orderBy('id','desc')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'=>$list['data'],
            'total'=>$list['total']
        ]);
    }
}
