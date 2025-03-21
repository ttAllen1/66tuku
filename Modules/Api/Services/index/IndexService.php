<?php

namespace Modules\Api\Services\index;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\Activite;
use Modules\Api\Models\CorpusType;
use Modules\Api\Models\IndexGuess;
use Modules\Api\Services\ad\AdService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;

class IndexService extends BaseApiService
{
    private $firstYear = 2020;

    /**
     * 启动图
     * @return JsonResponse
     * @throws ApiException
     */
    public function init_img(): JsonResponse
    {
        try{
            $data = (new AdService())->getAdListByPoi([7]);
        }catch (\Exception $exception) {
            throw new ApiException(['status'=>ApiMsgData::GET_API_ERROR,'message'=>$exception->getMessage()]);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data->toArray());
    }

    /**
     * 首页配置信息
     * @return JsonResponse
     * @throws ApiException
     */
    public function get_index(): JsonResponse
    {
        try{
            $data['announce'] = (new ConfigService())->getConfigs(['announce'])['announce'];
            $data['year_list'] = range($this->firstYear, date('Y'));
            $data['ad_list'] = (new AdService())->getAdListByPoi();
            if ($data['ad_list']) {
                foreach ($data['ad_list'] as $k => $v) {
                    $data['ad_list'][$k]['ad_image'] = str_replace(['api.48tkapi.com', 'api1.49tkaapi.com', 'api1.49tkapi8.com'], ConfigService::getAdImgUrl(), $v['ad_image']);
                }
            }
        }catch (\Exception $exception) {
            throw new ApiException(['status'=>ApiMsgData::GET_API_ERROR,'message'=>$exception->getMessage()]);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data);
    }

    /**
     * 获取年份列表
     * @return JsonResponse
     */
    public function years()
    {
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, range($this->firstYear, date('Y')));
    }

    /**
     * 获取彩种年份列表
     * @param $params
     * @return JsonResponse
     */
    public function yearsColor($params): JsonResponse
    {
        $year_list = range($this->firstYear, date('Y'));
        foreach ($year_list as $k => $year) {
            $arr[$k]['color'] = 1;
            $arr[$k]['name'] = $year."年彩色";
            $arr[$k]['year'] = $year;
        }
        foreach ($year_list as $k => $year) {
            $arr2[$k]['color'] = 2;
            $arr2[$k]['name'] = $year."年黑白";
            $arr2[$k]['year'] = $year;
        }
        $data = array_merge($arr, $arr2);
        $this->sortArrayByField($data, 'year', SORT_DESC);

        // 记录请求该接口的活动时间
        if ($userId = auth('user')->id()) {
            $ip = $this->getIp();
            $ip = filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0;
            Activite::query()->updateOrInsert([
                'user_id'       => $userId,
            ],[
                'request_ip'    => $ip,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s')
            ]);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data);
    }

    /**
     * 获取彩种黑白年份列表
     * @return JsonResponse
     */
    public function lotteryYearsColor(): JsonResponse
    {
//        if ($res = Redis::get('lottery_years_color')) {
//            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, json_decode($res, true));
//        }
        $data = $this->lotteryYears(true);
        foreach ($data as $k => $v) {
            foreach ($v['years'] as $kk => $vv) {
                $data[$k]['list1'][$kk]['year'] = $vv;
                $data[$k]['list1'][$kk]['color'] = 1;
                $data[$k]['list1'][$kk]['name'] = $vv."年彩色";
                $data[$k]['list2'][$kk]['year'] = $vv;
                $data[$k]['list2'][$kk]['color'] = 2;
                $data[$k]['list2'][$kk]['name'] = $vv."年黑色";
            }
        }
        foreach ($data as $k => $v) {
            $data[$k]['list'] = array_merge($v['list1'], $v['list2']);
            unset($data[$k]['years']);
            unset($data[$k]['list1']);
            unset($data[$k]['list2']);
        }
        foreach($data as $k => $v) {
            $this->sortArrayByField($data[$k]['list'], 'year', SORT_DESC);
        }
//        Redis::set('lottery_years_color', json_encode($data));
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data);
    }

    /**
     * 获取年份列表
     * @param bool $flag
     * @return array|JsonResponse
     */
    public function lotteryYears(bool $flag=false)
    {
        $data = [];
        foreach([1, 2, 3, 4, 5, 6, 7] as $k => $lotteryType) {
            $nextOpenDate = Redis::get('lottery_real_open_date_'.$lotteryType);
            $data[$k]['lotteryType'] = $lotteryType;
            if ($lotteryType == 7) {
                $data[$k]['years'] = range(Carbon::make($nextOpenDate)->format("Y"), 2024);
            } else {
                $data[$k]['years'] = range(Carbon::make($nextOpenDate)->format("Y"), 2020);
            }

        }
        if ($flag) {
            return $data;
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data);
    }

    /**
     * 首页资料列表
     * @param $params
     * @return JsonResponse
     */
    public function material($params): JsonResponse
    {
        $list = CorpusType::query()
            ->where('lotteryType', $params['lotteryType'])
            ->where('is_index', 1)
            ->orderBy('id')
            ->get(['id', 'lotteryType', 'corpusTypeName'])->toArray();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list);
    }

    /**
     * 首页猜测
     * @param $params
     * @return JsonResponse
     */
    public function guess($params): JsonResponse
    {
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, Cache::remember('guess_list_lotteryType_'.$params['lotteryType'], 60, function () use ($params) {
            return IndexGuess::query()
                ->where('lotteryType', $params['lotteryType'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['period', 'te_num', 'num_10', 'num_5', 'num_1', 'rec_9', 'rec_6', 'rec_3', 'rec_1'])
                ->toArray();
        }));
    }
}
