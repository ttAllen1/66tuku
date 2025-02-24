<?php

/**
 * @Name IP名单服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Modules\Admin\Models\Ip;
use Modules\Admin\Services\BaseApiService;

class IpsService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index(array $data){
        $list = Ip::query()
            ->when($data['type'], function ($query) use ($data){
                $query->where('type', $data['type']);
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
        return $this->commonCreate(Ip::query(),$data);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id, array $data){
        return $this->commonUpdate(Ip::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(Ip::query(),$id);
    }
}
