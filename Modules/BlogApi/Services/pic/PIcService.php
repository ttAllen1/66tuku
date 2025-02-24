<?php
/**
 * @Name  图片规则服务
 * @Description
 */

namespace Modules\BlogApi\Services\pic;


use Modules\BlogApi\Models\BlogPic;
use Modules\BlogApi\Services\BaseApiService;

class PicService extends BaseApiService
{
    /**
     * @name 图片列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.type  Int  类型:0=首页轮播图
     * @return JSON
     **/
    public function bannerList(int $type){
        $model = BlogPic::query();
        if($type>=0){
            $model = $model->where('type',$type);
        }
        $list = $model->select('id','image_id','url','content')
            ->with([
                'image_one'=>function($query){
                    $query->select('id','url','open');
                }
            ])
            ->where(['is_delete'=>0,'project_id'=>$this->projectId,'status'=>1])
            ->orderBy('sort','asc')
            ->orderBy('id','desc')
            ->get()
            ->toArray();
        $http = $this->getHttp();
        foreach ($list as $k=>$v){
            if($v['image_one']['open'] == 1){
                $list[$k]['image_url'] = $http .$v['image_one']['url'];
            }else{
                $list[$k]['image_url'] = $v['image_one']['url'];
            }
        }
        return $this->apiSuccess('',$list);
    }

}
