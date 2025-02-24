<?php
namespace Modules\Admin\Services\liuhe;

use Modules\Admin\Models\LiuheNumber;
use Modules\Admin\Models\LiuheYear;
use Modules\Admin\Services\BaseApiService;

class YearService extends BaseApiService
{
    /**
     * @name 号码列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = LiuheYear::query()
            ->orderBy('year', 'asc')
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
    public function update(int $id,array $data){
        return $this->commonUpdate(LiuheYear::query(),$id,$data);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {

        return $this->commonCreate(LiuheYear::query(), $data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(LiuheYear::query(),$id);
    }
}
