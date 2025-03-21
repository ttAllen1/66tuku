<?php
/**
 * @Name 标签管理服务
 * @Description
 */

namespace Modules\Blog\Services\label;


use Modules\Blog\Models\BlogLabel;
use Modules\Blog\Services\BaseApiService;

class LabelService extends BaseApiService
{
    /**
     * @name 列表数据
     * @description
     * @param  data Array 查询相关参数
     * @param  dtat.page Int 页码
     * @param  dtat.limit Int 每页显示条数
     * @param  dtat.name String 标签名称
     * @param  dtat.status Int 状态:0=禁用,1=启用
     * @param  dtat.created_at String 创建时间
     * @param  dtat.updated_at String 更新时间
     * @return JSON
     **/
    public function index(array $data){
        $model = BlogLabel::query();
        $model = $this->queryCondition($model,$data,'name');
        $list = $model->select('id','name','status','sort','created_at','updated_at')
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
     * @param  data.name String 标签名称
     * @param  data.status Int 状态:0=禁用,1=启用
     * @param  data.sort Int 排序
     * @return JSON
     **/
    public function store(array $data)
    {
        return $this->commonCreate(BlogLabel::query(),$data);
    }

    /**
     * @name 修改页面
     * @description
     * @param  id Int 标签ID
     * @return JSON
     **/
    public function edit(int $id){
        return $this->apiSuccess('',BlogLabel::select('id','name',  'status', 'sort')->find($id)->toArray());
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     * @param  id Int 标签ID
     * @param  data.name String 标签名称
     * @param  data.status Int 状态:0=禁用,1=启用
     * @param  data.sort Int 排序
     * @return JSON
     **/
    public function update(int $id,array $data){
        return $this->commonUpdate(BlogLabel::query(),$id,$data);
    }
    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     * @param  id Int 标签ID
     * @param  data.status Int 状态（0或1）
     * @return JSON
     **/
    public function status(int $id,array $data){
        return $this->commonStatusUpdate(BlogLabel::query(),$id,$data);
    }

    /**
     * @name 排序
     * @description
     * @param  data Array 调整数据
     * @param  id Int 标签ID
     * @param  data.sort Int 排序
     * @return JSON
     **/
    public function sorts(int $id,array $data){
        return $this->commonSortsUpdate(BlogLabel::query(),$id,$data);
    }
    /**
     * @name 删除
     * @description
     * @param id Int 标签ID
     * @return JSON
     **/
    public function cDestroy(int $id){
        return $this->commonDestroy(BlogLabel::query(),[$id]);
    }
}
