<?php
/**
 * @Name 用户经验值规则服务
 * @Description
 */

namespace Modules\Blog\Services\empiricalValue;


use Modules\Blog\Models\BlogEmpiricalValue;
use Modules\Blog\Services\BaseApiService;

class EmpiricalValueService extends BaseApiService
{
    /**
     * @name 列表数据
     * @description
     * @param  data Array 查询相关参数
     * @param  dtat.page Int 页码
     * @param  dtat.limit Int 每页显示条数
     * @param  dtat.name String 规则名称
     * @param  dtat.status Int 状态:0=禁用,1=启用
     * @param  dtat.created_at String 创建时间
     * @param  dtat.updated_at String 更新时间
     * @return JSON
     **/
    public function index(array $data){
        $model = BlogEmpiricalValue::query();
        $model = $this->queryCondition($model,$data,'name');
        $list = $model->select('id','name','status','sort','content','value','restrict_value','created_at','updated_at')
            ->where(['is_delete'=>0,'project_id'=>$this->projectId])
            ->orderBy('sort','asc')
            ->orderBy('id','desc')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'=>$list['data'],
            'total'=>$list['total']
        ]);
    }
    /**
     * @name 添加
     * @description
     * @param  data Array 添加数据
     * @param  data.name String 规则名称
     * @param  data.content String 规则描述
     * @param  data.status Int 状态:0=禁用,1=启用
     * @param  data.sort Int 排序
     * @param  data.value Int 获取经验值
     * @param  data.restrict_value Int 限制经验值，以天为单位，0表示没有限制
     * @return JSON
     **/
    public function store(array $data)
    {
        $data['project_id'] = $this->projectId;
        return $this->commonCreate(BlogEmpiricalValue::query(),$data);
    }

    /**
     * @name 修改页面
     * @description
     * @param  id Int 级别规则ID
     * @return JSON
     **/
    public function edit(int $id){
        return $this->apiSuccess('',BlogEmpiricalValue::select('id','name', 'content', 'status', 'sort', 'value', 'restrict_value')->find($id)->toArray());
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     * @param  id Int 经验值规则ID
     * @param  data.name String 规则名称
     * @param  data.content String 规则描述
     * @param  data.status Int 状态:0=禁用,1=启用
     * @param  data.sort Int 排序
     * @param  data.value Int 获取经验值
     * @param  data.restrict_value Int 限制经验值，以天为单位，0表示没有限制
     * @return JSON
     **/
    public function update(int $id,array $data){
        return $this->commonUpdate(BlogEmpiricalValue::query(),$id,$data);
    }
    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     * @param  id Int 经验值规则ID
     * @param  data.status Int 状态（0或1）
     * @return JSON
     **/
    public function status(int $id,array $data){
        return $this->commonStatusUpdate(BlogEmpiricalValue::query(),$id,$data);
    }

    /**
     * @name 排序
     * @description
     * @param  data Array 调整数据
     * @param  id Int 经验值规则ID
     * @param  data.sort Int 排序
     * @return JSON
     **/
    public function sorts(int $id,array $data){
        return $this->commonSortsUpdate(BlogEmpiricalValue::query(),$id,$data);
    }
    /**
     * @name 删除
     * @description
     * @param id Int 经验值规则ID
     * @return JSON
     **/
    public function cDestroy(int $id){
        return $this->commonIsDelete(BlogEmpiricalValue::query(),[$id]);
    }
}
