<?php
/**
 * @Name 活动管理服务
 * @Description
 */

namespace Modules\Admin\Services\humorous;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\Humorous;
use Modules\Api\Services\picture\PictureService;

class HumorousService extends BaseApiService
{
    /**
     * @name 幽默竞猜列表
     * @description
     **/
    public function index(array $data)
    {
        $list = Humorous::query()
            ->when($data['lotteryType'] != 0, function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
//            ->where('year', date('Y'))
//            ->orderBy('id', 'desc')
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
        if ($data['imageUrl'] && !$data['width']) {
            $info = (new PictureService())->getImageInfo($data['imageUrl']);
            $data['width'] = $info['width'];
            $data['height'] = $info['height'];
        }

        return $this->commonUpdate(Humorous::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        foreach($id as $item) {
            Humorous::query()->find($item)->comments()->delete();
        }
        return $this->commonDestroy(Humorous::query(),$id);
    }

    public function store(array $data)
    {
        try{
            $data['issue']      = (int)$data['issue'];
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['title']      = $data['year']. '年' .str_pad($data['issue'], 3, 0, STR_PAD_LEFT) . '期';
            unset($data['id']);
            if (!empty($data['imageUrl'])) {
                $info = (new PictureService())->getImageInfo($data['imageUrl']);
                $data['width'] = $info['width'];
                $data['height'] = $info['height'];
            }
            $res = DB::table('humorous')->updateOrInsert(
                [
                    'year'=> $data['year'], 'lotteryType'=>$data['lotteryType'], 'issue'=>$data['issue']
                ], $data);
            if ($res) {
                return $this->apiSuccess('');
            }
            return $this->apiError('');
        }catch (\Exception $exception) {
            return $this->apiError('');
        }
    }
}
