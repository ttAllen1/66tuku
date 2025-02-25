<?php
namespace Modules\Admin\Services\liuhe;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\LiuheConfig;
use Modules\Admin\Models\LotterySet;
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

    public function liuhe_lottery_index(array $data): JsonResponse
    {
        $list = lotterySet::query()
            ->orderBy('lotteryType')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }

    public function liuhe_lottery_delete($id)
    {
        return $this->commonDestroy(LotterySet::query(),[$id]);
    }

    public function liuhe_lottery_update($id, $params)
    {
        return $this->commonUpdate(LotterySet::query(),$id, $params);
    }

    public function liuhe_lottery_create($data)
    {
        return $this->commonCreate(LotterySet::query(), $data);
    }
}
