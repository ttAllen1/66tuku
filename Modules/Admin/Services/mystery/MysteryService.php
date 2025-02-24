<?php
/**
 * @Name 活动管理服务
 * @Description
 */

namespace Modules\Admin\Services\mystery;

use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\Mystery;

class MysteryService extends BaseApiService
{
    /**
     * @name 幽默竞猜列表
     * @description
     **/
    public function index(array $data)
    {
        $list = Mystery::query()
            ->when($data['lotteryType'] != 0, function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
//            ->where('year', date('Y'))
            ->orderBy('year', 'desc')
            ->orderBy('issue', 'desc')
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total'],
        ]);
    }

    public function update($id,array $data)
    {
        return $this->commonUpdate(Mystery::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(Mystery::query(),$id);
    }

    public function store(array $data)
    {
        $data['issue'] = ltrim($data['issue'], 0);
        $data['content'] = json_encode($data['content']);
        return $this->commonCreate(Mystery::query(), $data);
    }
}
