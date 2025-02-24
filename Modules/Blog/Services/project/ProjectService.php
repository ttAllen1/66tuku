<?php
/**
 * @Name 系统配置服务
 * @Description
 */

namespace Modules\Blog\Services\project;


use Modules\Blog\Models\AuthProject;
use Modules\Blog\Services\BaseApiService;

class ProjectService extends BaseApiService
{
    /**
     * @name 修改页面
     * @description
     * @return JSON
     **/
    public function index(){
        $info = AuthProject::with([
            'logo_one'=>function($query){
                $query->select('id','url','open');
            },
            'ico_one'=>function($query){
                $query->select('id','url','open');
            }
        ])
            ->find($this->projectId)->toArray();
        $http = $this->getHttp();
        if($info['logo_one']['open'] == 1){
            $info['logo_url'] = $http.$info['logo_one']['url'];
        }else{
            $info['logo_url'] = $info['image_one']['url'];
        }
        if($info['ico_one']['open'] == 1){
            $info['ico_url'] = $http.$info['ico_one']['url'];
        }else{
            $info['ico_url'] = $info['ico_one']['url'];
        }
        return $this->apiSuccess('',$info);
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     * @param  id Int 项目id
     * @param  data.name String 项目名称
     * @param  data.url String 项目地址
     * @param  data.logo_id Int 站点logo
     * @param  data.ico_id Int 站点标识
     * @param  data.description String 项目描述
     * @param  data.keywords String 项目关键词
     * @param  data.status Int 状态:0=禁用,1=启用
     * @return JSON
     **/
    public function update(int $id,array $data){
        return $this->commonUpdate(AuthProject::query(),$id,$data);
    }
}
