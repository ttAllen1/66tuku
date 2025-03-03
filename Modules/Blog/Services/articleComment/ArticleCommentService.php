<?php

/**
 * @Name 文章评论模型
 * @Description
 */

namespace Modules\Blog\Services\articleComment;


use Modules\Blog\Models\BlogArticleComment;
use Modules\Blog\Services\BaseApiService;

class ArticleCommentService extends BaseApiService
{
    /**
     * @name 列表数据
     * @description
     * @param  data Array 查询相关参数
     * @param  data.title String 文章标题
     * @param  data.pid   Int   上级id
     * @param  data.created_at String 创建时间
     * @param  data.nickname String 昵称
     * @return JSON
     **/
    public function index(array $data){
        $model = BlogArticleComment::query();
        $model = $this->queryCondition($model,$data,'');
        $model = $model->where('pid',$data['pid']);
        if (empty($data['title'])){
            return $this->apiSuccess('',[]);
        }
        $list = $model->select('id','user_id','content','article_id','pid','status','sort','created_at')
            ->where(['project_id'=>$this->projectId])
            ->with([
                'article_to'=>function($query){
                    $query->select('id','title');
                },
                'user_to'=>function($query){
                    $query->select('id','user_id');
                },
                'user_to.user_to'=>function($query){
                    $query->select('id','nickname','name','email');
                },
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
            ->orderBy('sort','asc')
            ->orderBy('id','desc')
            ->get()->toArray();
        foreach ($list as $k=>$v){
            $list[$k]['hasChildren'] = true;
        }
        return $this->apiSuccess('',$list);
    }

    /**
     * @name 删除
     * @description
     * @param id Int 权限id
     * @return JSON
     **/
    public function cDestroy(int $id,int $articleId){
        $idArr = $this->ids($id,$articleId);
        return $this->commonDestroy(BlogArticleComment::query(),$idArr);
    }
    /**
     * @name 获取菜单id
     * @description
     * @param id Int 当前删除数据id
     * @return Array
     **/
    private function ids(int $id,int $articleId):Array
    {
        $rule = BlogArticleComment::select('id','pid')->where(['project_id'=>$this->projectId,'article_id'=>$articleId])->get()->toArray();
        $arr = $this->delSort($rule,$id);
        $arr[] = $id;
        return $arr;
    }
    /**
     * @name 递归遍历数据
     * @description
     * @param id Int 当前删除数据id
     * @param rule Array 列表信息
     * @return Array 返回获取当前的删除id的其他子id
     **/
    public function delSort(array $rule,int $id):Array
    {
        //创建新数组
        static $arr=array();
        foreach($rule as $k=>$v){
            if($v['pid'] == $id){
                $arr[] = $v['id'];
                unset($rule[$k]);
                $this->delSort($rule,$v['id']);
            }
        }
        return $arr;
    }
    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     * @param  id Int 评论ID
     * @param  data.status Int 状态（0或1）
     * @return JSON
     **/
    public function status(int $id,array $data){
        return $this->commonStatusUpdate(BlogArticleComment::query(),$id,$data);
    }
    /**
     * @name 排序
     * @description
     * @param  data Array 调整数据
     * @param  id Int 评论ID
     * @param  data.sort Int 排序
     * @return JSON
     **/
    public function sorts(int $id,array $data){
        return $this->commonSortsUpdate(BlogArticleComment::query(),$id,$data);
    }
}
