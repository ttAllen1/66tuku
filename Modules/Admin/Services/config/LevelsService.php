<?php

/**
 * @Name 会员等级服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Intervention\Image\Exception\ImageException;
use Intervention\Image\Facades\Image;
use Modules\Admin\Models\Level;
use Modules\Admin\Services\BaseApiService;

class LevelsService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index(array $data){
        $list = Level::query()
            ->when($data['status'], function ($query) use ($data){
                $query->where('status', $data['status']);
            })
            ->when($data['level_name'], function ($query) use ($data){
                $query->where('level_name', 'like', '%'.$data['level_name'].'%');
            })
            ->with(['images'])
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
        $level_name = $data['level_name'];
        $medal = $data['medal'];
        if (!$level_name || !$medal) {
            throw new \InvalidArgumentException('参数不对');
        }
        if (Level::query()->where('level_name', $level_name)->value('id')) {
            $this->apiError('用户等级：'.$level_name.' 已存在');
        }
        $data['img_url'] = $data['medal'];
        unset($data['medal']);
        Level::query()->create($data);

        return $this->apiSuccess('添加成功');
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id, array $data){
        $level_name = $data['level_name'];
        $medal = $data['medal'];
        $id = $data['id'];
        if (!$level_name || !$medal || !$id) {
            throw new \InvalidArgumentException('参数不对');
        }
        $data['img_url'] = $data['medal'];

        unset($data['img_id']);
        unset($data['medal']);
        return $this->commonUpdate(Level::query(),$id,$data);
    }
}
