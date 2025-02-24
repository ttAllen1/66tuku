<?php
/**
 * @Name 广告管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\Ad;
use Modules\Admin\Services\BaseApiService;

class AdService extends BaseApiService
{
    /**
     * @name 广告列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = Ad::query()
            ->when($data['type'], function ($query) use ($data){
                $query->where('type', $data['type']);
            })
            ->when($data['status'], function ($query) use ($data){
                $query->where('status', $data['status']);
            })
            ->when($data['lotteryType'], function ($query) use ($data){
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->when($data['title'], function ($query) use ($data){
                $query->where('title', 'like', '%'.$data['title'].'%');
            })
            ->when($data['position_type'], function ($query) use ($data){
                $query->where('position', $data['position_type']);
            })
            ->when($data['date_range'], function($query) use ($data) {
                $query->whereDate('start_open_with', '>=', $this->getDateFormat($data['date_range'][0]))
                      ->whereDate('end_open_with', '<=', $this->getDateFormat($data['date_range'][1]));
            })
            ->latest()
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'position_type' => Ad::$_position_type,
            'total'         => $list['total']
        ]);
    }

    /**
     * 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        if (!empty($data['start_open_with'])) {
            $data['start_open_with'] = Carbon::parse($data['start_open_with'])->toDateString();
        }
        if (!empty($data['end_open_with'])) {
            $data['end_open_with'] = Carbon::parse($data['end_open_with'])->toDateString();
        }
        if (in_array(0, $data['lotteryType'])) {
            $data['lotteryType'] = [0];
        }
        $arr = [];
        $arr1 = [];
        Redis::del('cache_ad_list_by_index');
        foreach ($data['position'] as $k => $position) {
            Redis::del('cache_ad_list_by_'.$position);
            $arr[$k] = $data;
            unset($arr[$k]['position']);
            $arr[$k]['position'] = $position;
            $arr[$k]['created_at'] = date('Y-m-d H:i:s', time());
        }
        foreach ($arr as $item) {
            foreach ($item["lotteryType"] as $lotteryType) {
                $newItem = $item;
                $newItem["lotteryType"] = $lotteryType;
                $arr1[] = $newItem;
            }
        }
        Ad::query()->insert($arr1);
        return $this->apiSuccess('新增成功');
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id,array $data)
    {
        $arr = [];
        $arr1 = [];
        Redis::del('cache_ad_list_by_index');
        foreach ($data['position'] as $k => $position) {
            Redis::del('cache_ad_list_by_'.$position);
            $arr[$k] = $data;
            unset($arr[$k]['position']);
            $arr[$k]['position'] = $position;
            $arr[$k]['created_at'] = date('Y-m-d H:i:s', time());
        }
        foreach ($arr as $item) {
            foreach ($item["lotteryType"] as $lotteryType) {
                $newItem = $item;
                $newItem["lotteryType"] = $lotteryType;
                $arr1[] = $newItem;
            }
        }
        Ad::query()->where('id', $id)->delete();
        Ad::query()->insert($arr1);

        return $this->apiSuccess('修改成功');
    }

    /**
     * @name 修改提交批量
     * @description
     * @param  data Array 修改数据
     **/
    public function update_batch($id,array $data)
    {
        Redis::del('cache_ad_list_by_index');
        return $this->commonUpdate(Ad::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        $res = Ad::query()->whereIn('id', $id)->get();
        Redis::del('cache_ad_list_by_index');
        if ($res->isNotEmpty()) {
            foreach ($res as $item) {
                Redis::del('cache_ad_list_by_'.$item['position']);
            }
        }

        return $this->commonDestroy(Ad::query(),$id);
    }
}
