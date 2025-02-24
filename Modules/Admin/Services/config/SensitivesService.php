<?php

/**
 * @Name 站内信服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Carbon\Carbon;
use Modules\Admin\Models\Sensitive;
use Modules\Admin\Models\StationMsg;
use Modules\Admin\Models\UserMessage;
use Modules\Admin\Services\BaseApiService;
use Modules\Admin\Services\user\UserMessageService;

class SensitivesService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index(array $data){
        $list = Sensitive::query()
            ->when($data['status'], function ($query) use ($data){
                $query->where('status', $data['status']);
            })
            ->when($data['keyword'], function ($query) use ($data){
                $query->where('keyword', 'like', '%'.$data['keyword'].'%');
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
        return $this->commonCreate(Sensitive::query(),$data);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id, array $data){
        return $this->commonUpdate(Sensitive::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(Sensitive::query(),$id);
    }
}
