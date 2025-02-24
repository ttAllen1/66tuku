<?php
namespace Modules\Admin\Services\liuhe;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\LiuheOpenDay;
use Modules\Admin\Services\BaseApiService;

class OpenDateService extends BaseApiService
{
    /**
     * 列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = LiuheOpenDay::query()
            ->orderBy('lotteryType', 'asc')
            ->orderBy('open_date')
            ->when($data['lotteryType'], function($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->when($data['year'], function($query) use ($data) {
                $query->where('year', $data['year']);
            })
            ->when($data['month'], function($query) use ($data) {
                $query->where('month', $data['year'].'-'.str_pad($data['month'], 2, 0, STR_PAD_LEFT));
            })
            ->paginate($data['limit'])
            ->toArray();
        $switch = Redis::get('lottery_open_day_switch_closed_by_month_'.date('Y-m'));

        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total'],
            'switch'        => (bool)$switch
        ]);
    }

    public function switch($params)
    {
        try{
            Redis::set('lottery_open_day_switch_closed_by_month_'.date('Y-m'), !(bool)$params['switch']);

            return $this->apiSuccess();
        }catch (\Exception $exception) {
            return $this->apiError();
        }
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id,array $data){
        return $this->commonUpdate(LiuheOpenDay::query(),$id,$data);
    }
    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     **/
    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(LiuheOpenDay::query(),$id);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        try{
            $date = Carbon::parse($data['open_date']);
            $data['open_date'] = $date->year.'-'.str_pad($date->month, 2, 0, STR_PAD_LEFT).'-'.str_pad($date->day, 2, 0, STR_PAD_LEFT);
            $data['year'] =  $date->year;
            $data['month'] =  $date->year.'-'.str_pad($date->month, 2, 0, STR_PAD_LEFT);
            $data['created_at'] = date('Y-m-d H:i:s');
            if (!$data['lotteryType']) {
                return $this->apiError('请选择彩种');
            }
            $res = DB::table('liuhe_open_days')->updateOrInsert(
                ["year"=>$data['year'], "lotteryType"=>$data['lotteryType'], "open_date"=>$data['open_date']],
                $data
            );
            if ($res) {
                return $this->apiSuccess();
            }
            return $this->apiError();
        }catch (QueryException $exception) {
            Log::error('开奖时间编辑错误', ['message'=>$exception->getMessage()]);
            return $this->apiError();
        }
    }

}
