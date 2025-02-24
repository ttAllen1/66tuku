<?php
/**
 * @Name 禁言管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Modules\Admin\Models\Mushin;
use Modules\Admin\Services\BaseApiService;

class MushinService extends BaseApiService
{
    /**
     * @name 禁言列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = Mushin::query()
            ->when($data['status'], function ($query) use ($data){
                $query->where('status', $data['status']);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        return $this->commonCreate(Mushin::query(),$data);
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update(int $id,array $data){
        return $this->commonUpdate(Mushin::query(),$id,$data);
    }

    public function getMushins()
    {
        return Mushin::query()->where('status', 1)->select('id', 'name', 'mushin_period')->get()->toArray();
    }
}
