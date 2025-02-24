<?php
namespace Modules\Admin\Services\liuhe;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\LiuheConfig;
use Modules\Admin\Services\BaseApiService;

class LiuHeConfigService extends BaseApiService
{
    /**
     * 列表数据
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $list = LiuheConfig::query()
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
        return $this->commonUpdate(LiuheConfig::query(),$id,$data);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        return $this->commonCreate(LiuheConfig::query(), $data);
    }
}
