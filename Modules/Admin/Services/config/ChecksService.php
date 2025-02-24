<?php

/**
 * @Name 审核服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Modules\Admin\Models\Check;
use Modules\Admin\Services\BaseApiService;

class ChecksService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index(array $data){
        $list = Check::query()
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
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id, array $data){

        $id = $data['id'];
        return $this->commonUpdate(Check::query(),$id,$data);
    }
}
