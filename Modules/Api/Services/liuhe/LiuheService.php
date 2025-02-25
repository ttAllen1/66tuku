<?php
namespace Modules\Api\Services\liuhe;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\LotterySet;
use Modules\Api\Models\HistoryNumber;
use Modules\Api\Models\LiuheNumber;
use Modules\Api\Models\LiuheOpenDay;
use Modules\Api\Models\LotteryVideo;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class LiuheService extends BaseApiService
{
    /**
     * 获取基础属性--波色 生肖  五行 家禽野兽
     * @return JsonResponse
     * @throws CustomException
     */
    public function get_number_attr(): JsonResponse
    {
        return $this->get_number_attr2();
//        if (date('Y-m-d')<='2024-02-09') {
//            $year = 2023;
//        } else {
//            $year = 2024;
//        }
//        $year = 2024;
//        $nums = LiuheNumber::query()->where('year', $year)
//            ->get()->toArray();
//        if( !$nums ) {
//            throw new CustomException(['message'=>'号码不存在']);
//        }
//        $result = array_reduce($nums, function($carry, $item) {
//            $name = $item['bose'];
//            $carry['colorList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
//            $name = $item['five_elements'];
//            $carry['wuxingList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
//            $name = $item['zodiac'];
//            $carry['shengxiaoList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
//            $name = $item['sky_land'];
//            $carry['skyLandList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
//            $name = $item['four_arts'];
//            $carry['fourArtsList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
//
//            return $carry;
//        }, []);
//
//        $res = [];
//        foreach ($result as $k => $v) {
//            if ($k == 'colorList') {
//                foreach ($v as $kk => $vv) {
//                    $res[$k][$kk]['name'] = $kk.'波';
//                    $res[$k][$kk]['list'] = $vv;
//                }
//            } else if ($k == 'wuxingList') {
//                foreach ($v as $kk => $vv) {
//                    $res[$k][$kk]['name'] = $kk;
//                    $res[$k][$kk]['list'] = $vv;
//                }
//            } else if ($k == 'shengxiaoList') {
//                foreach ($v as $kk => $vv) {
//                    $res[$k][$kk]['name'] = $kk;
//                    $res[$k][$kk]['list'] = $vv;
//                }
//            } else if ($k == 'skyLandList') {
//                foreach ($v as $kk => $vv) {
//                    $res[$k][$kk]['name'] = $kk;
//                    $res[$k][$kk]['list'] = $vv;
//                }
//            } else if ($k == 'fourArtsList') {
//                foreach ($v as $kk => $vv) {
//                    $res[$k][$kk]['name'] = $kk;
//                    $res[$k][$kk]['list'] = $vv;
//                }
//            }
//        }
//        sort($res['colorList']);
//        sort($res['wuxingList']);
//        sort($res['shengxiaoList']);
//        sort($res['skyLandList']);
//        sort($res['fourArtsList']);
//        $res['animalTypeList'] = [
//            [
//                "name"  => '家禽',
//                "list"  => $this->jiaqin
//            ],
//            [
//                "name"  => '野兽',
//                "list"  => $this->yeshou
//            ]
//        ];
//        foreach ($res['wuxingList'] as $k => $v) {
//            if ($v['name'] == '金') {
//                $res['wuxingList'][0] = $v;
//            } else if ($v['name'] == '木') {
//                $res['wuxingList'][1] = $v;
//            } else if ($v['name'] == '水') {
//                $res['wuxingList'][2] = $v;
//            } else if ($v['name'] == '火') {
//                $res['wuxingList'][3] = $v;
//            } else if ($v['name'] == '土') {
//                $res['wuxingList'][4] = $v;
//            }
//        }
//
//        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 获取基础属性--波色 生肖  五行 家禽野兽 农历
     * @return JsonResponse
     * @throws CustomException
     */
    public function get_number_attr2(): JsonResponse
    {
        $year = 2025;
        if (date('m-d') != "01-29" && $redis_number_attr = Redis::get('number_attr')) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, json_decode($redis_number_attr, true));
        }
        $nums = LiuheNumber::query()->where('year', $year)
            ->get()->toArray();
        if( !$nums ) {
            throw new CustomException(['message'=>'号码不存在']);
        }
        $result = array_reduce($nums, function($carry, $item) {
            $name = $item['bose'];
            $carry['colorList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
            $name = $item['five_elements'];
            $carry['wuxingList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
            $name = $item['zodiac'];
            $carry['shengxiaoList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
            $name = $item['sky_land'];
            $carry['skyLandList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];
            $name = $item['four_arts'];
            $carry['fourArtsList'][$name][] = ['color'=>($item['bose'] == '红' ? 1 : ($item['bose'] == '蓝' ? 2 : 3)), 'number'=>str_pad($item['number'], 2, 0, STR_PAD_LEFT)];

            return $carry;
        }, []);

        $res = [];
        foreach ($result as $k => $v) {
            if ($k == 'colorList') {
                foreach ($v as $kk => $vv) {
                    $res[$k][$kk]['name'] = $kk.'波';
                    $res[$k][$kk]['list'] = $vv;
                }
            } else if ($k == 'wuxingList') {
                foreach ($v as $kk => $vv) {
                    $res[$k][$kk]['name'] = $kk;
                    $res[$k][$kk]['list'] = $vv;
                }
            } else if ($k == 'shengxiaoList') {
                foreach ($v as $kk => $vv) {
                    $res[$k][$kk]['name'] = $kk;
                    $res[$k][$kk]['list'] = $vv;
                }
            } else if ($k == 'skyLandList') {
                foreach ($v as $kk => $vv) {
                    $res[$k][$kk]['name'] = $kk;
                    $res[$k][$kk]['list'] = $vv;
                }
            } else if ($k == 'fourArtsList') {
                foreach ($v as $kk => $vv) {
                    $res[$k][$kk]['name'] = $kk;
                    $res[$k][$kk]['list'] = $vv;
                }
            }
        }
        sort($res['colorList']);
        sort($res['wuxingList']);
        sort($res['shengxiaoList']);
        sort($res['skyLandList']);
        sort($res['fourArtsList']);
        $res['animalTypeList'] = [
            [
                "name"  => '家禽',
                "list"  => $this->jiaqin
            ],
            [
                "name"  => '野兽',
                "list"  => $this->yeshou
            ]
        ];
        foreach ($res['wuxingList'] as $k => $v) {
            if ($v['name'] == '金') {
                $res['wuxingList'][0] = $v;
            } else if ($v['name'] == '木') {
                $res['wuxingList'][1] = $v;
            } else if ($v['name'] == '水') {
                $res['wuxingList'][2] = $v;
            } else if ($v['name'] == '火') {
                $res['wuxingList'][3] = $v;
            } else if ($v['name'] == '土') {
                $res['wuxingList'][4] = $v;
            }
        }
        Redis::set('number_attr', json_encode($res));
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 获取某期开奖详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function get_number($params): JsonResponse
    {
        $year           = $params['year'];
        $lotteryType    = $params['lotteryType'];

        if (!empty($params['issue'])) {
            $issue = $params['issue'];
        } else {
            // 根据 年份 彩种 获取最新期数
            $issue = HistoryNumber::query()
                ->where('year', $year)
                ->where('lotteryType', $lotteryType)
                ->orderBy('issue', 'desc')
                ->value('issue');
        }
        $issue = str_pad($issue, 3, 0, STR_PAD_LEFT);
        try{
            $number = HistoryNumber::query()
                ->where('year', $year)
                ->where('lotteryType', $lotteryType)
                ->where('issue', $issue)
                ->firstOrFail();

            $next = LiuheOpenDay::query()
                ->where('lotteryType', $lotteryType)
                ->where('open_date', '>=', date('Y-m-d'))
                ->select('open_date')
                ->first();
            if (!$next) {
                $data['lotteryTime'] = '';
                $data['lotteryWeek'] = '';
                $data['issue'] = '';
            } else {
                $data['lotteryTime'] = $next['open_date'];
                $data['lotteryWeek'] = $this->dayOfWeek($next['open_date']);
                $data['issue']       = Redis::get('lottery_real_open_issue_'.$lotteryType);
            }
            $number['nextData'] = $data;

            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $number->toArray());
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'该期数据不存在']);
        }
    }

    /**
     * 历史号码
     * @param $params
     * @return JsonResponse
     */
    public function history($params): JsonResponse
    {
        $history = HistoryNumber::query()
//            ->where('year', $params['year'])
            ->where('lotteryType', $params['lotteryType'])
            ->orderByDesc('year')
            ->orderBy('issue', $params['sort'])
            ->simplePaginate();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $history->toArray());
    }

    /**
     * 号码推荐列表
     * @param $params
     * @return JsonResponse
     */
    public function recommend($params): JsonResponse
    {
        return (new RecommendsService())->number_recommend($params);
    }

    /**
     * 开奖记录详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function record($params): JsonResponse
    {
        try {
            $res = HistoryNumber::query()
                ->with(['recommend'])
                ->findOrFail($params['id'])->toArray();
            foreach ($res['number_attr'] as $k => $item) {
                if ($k<6) {
                    $arr[] = $item['number'];
                }
            }
            $res['attr'] = $this->getZhengMaAttr($arr);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'数据不存在']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 六合统计
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function statistics($params): JsonResponse
    {
        $init = range(1, 49);
        $init_arr = array_map(function($a){
            return str_pad($a, 2, '0', STR_PAD_LEFT);
        }, $init);

        $lotteryType    = $params['lotteryType'];
        $issue          = $params['issue'];
        $lists = HistoryNumber::query()->where('lotteryType', $lotteryType)
            ->where('issue', '<>', 0)
            ->orderBy('lotteryTime', 'desc')
            ->limit($issue)
            ->get();
        if ($lists->isEmpty()) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        $lists = $lists->toArray();
//        dd($lists);
        $teMa = [];
        $teMaBs = [];
        $teMaTail = [];
        $teMaShengXiao = [];
        $zhengMa = [];
        $zhengMaBs = [];
        $zhengMaShengXiao = [];
        foreach ($lists as $k => $list) {
            $teMa[] = $list['te_attr']['number'];
            $teMaTail[] = substr($list['te_attr']['number'], 1, 1);
            $teMaBs[] = $list['te_attr']['color'];
            $teMaShengXiao[] = $list['te_attr']['shengXiao'];
            foreach ($list['number_attr'] as $kk => $value) {
                if ( $kk>5) {
                    continue;
                }
                $zhengMa[] = $value['number'];
                $zhengMaBs[$k][] = $value['color'];
                $zhengMaShengXiao[$k][] = $value['shengXiao'];
            }
        }
//        dd($teMa, $lists);
        // 特码出现最多的号码
        $teMaCounts = array_count_values($teMa);
        ksort($teMaCounts);  // 根据关联数组的键，对数组进行升序排列
        arsort($teMaCounts); // 根据关联数组的值，对数组进行降序排列
        $res['specialHotNumberList'] = $this->tongJi(array_slice($teMaCounts, 0, 10, true)); // 特码出现次数最多

        // 特码当前遗漏期数最多的号码
        $res['specialColdNumberList'] = $this->forgetNumber($teMaCounts, $init_arr, $issue); // 特码遗漏出现次数最多

        // 特码出现最多的号码
        $zhengMaCounts = array_count_values($zhengMa);
        ksort($zhengMaCounts);  // 根据关联数组的键，对数组进行升序排列
        arsort($zhengMaCounts); // 根据关联数组的值，对数组进行降序排列
        $zhengMaCountsLimit = array_slice($zhengMaCounts, 0, 10, true);
        $res['normalHotNumberList'] = $this->tongJi($zhengMaCountsLimit); // 正码出现次数最多

        // 正码当前遗漏期数最多的号码
        $res['normalColdNumberList'] = $this->forgetNumber($zhengMaCounts, $init_arr, $issue);

        // 特码波色出现最多
        $teMaBsCounts = array_count_values($teMaBs);
        $res['specialHotColorList'] = $this->forgetColor($teMaBsCounts);

        // 特码波色遗漏最多
        $res['specialColdColorList'] = $this->forgetColor($teMaBsCounts, true, $issue);

        // 特码生肖出现最多
        $teMaShengXiaoCounts = array_count_values($teMaShengXiao);
        $res['specialHotAnimalList'] = $this->tongJiSx($teMaShengXiaoCounts);

        // 特码生肖遗漏最多
        $res['specialColdAnimalList'] = $this->forgetSx($teMaShengXiaoCounts, $issue);

        // 正码波色出现最多
        foreach ($zhengMaBs as $k => $v) {
            $zhengMaBs[$k] = array_unique($v);
        }
        $zhengMaBsAll = array_merge(...array_values($zhengMaBs));
        $zhengMaBsCounts = array_count_values($zhengMaBsAll);
        $res['normalHotColorList'] = $this->forgetColor($zhengMaBsCounts);

        // 正码波色遗漏最多
        $res['normalColdColorList'] = $this->forgetColor($zhengMaBsCounts, true, $issue);

        // 特码尾数出现最多
        $teMaTailCounts = array_count_values($teMaTail);

        $res['specialHotTailList'] = $this->tongJiTail($teMaTailCounts);
        $res['specialColdTailList'] = $this->forgetTail($teMaTailCounts, $issue);

        // 正码生肖出现最多
        foreach ($zhengMaShengXiao as $k => $v) {
            $zhengMaShengXiao[$k] = array_unique($v);
        }
        $zhengMaShengXiaoAll = array_merge(...array_values($zhengMaShengXiao));
        $zhengMaShengXiaoCounts = array_count_values($zhengMaShengXiaoAll);
        $res['normalHotAnimalList'] = $this->tongJiSx($zhengMaShengXiaoCounts);

        // 正码生肖遗漏最多
        $res['normalColdAnimalList'] = $this->forgetSx($zhengMaShengXiaoCounts, $issue);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 开奖时间列表
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function open_date($params): JsonResponse
    {
        $y_m = date('Y-m');
        $cacheKey = 'lottery_open_date_' . $params['lotteryType'] . $y_m;
        $open_date = Cache::get($cacheKey);
        if (!$open_date) {
            $days = LiuheOpenDay::query()->where('lotteryType', $params['lotteryType'])
                ->where('month', $y_m)
                ->orderBy('open_date')
                ->get();
            if ($days->isEmpty()) {
                throw new CustomException(['message' => '数据不存在']);
            }
            $open_date = [];
            foreach ($days as $day) {
                $open_date['month'] = $y_m;
                $open_date['dayList'][] = $day['open_date'];
            }
            $expiresAt = Carbon::now()->addMinutes(1);
            Cache::put($cacheKey, $open_date, $expiresAt);
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $open_date);
    }

    /**
     * 下一期开奖时间
     * @return JsonResponse
     */
    public function next(): JsonResponse
    {
        $arr = [
            [
                'lotteryType'           => 1,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '21点30分',
            ],
            [
                'lotteryType'           => 2,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '21点30分'
            ],
            [
                'lotteryType'           => 3,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '20点50分'
            ],
            [
                'lotteryType'           => 4,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '18点40分'
            ],
            [
                'lotteryType'           => 5,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '22点33分'
            ],
            [
                'lotteryType'           => 6,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '21点30分'
            ],
            [
                'lotteryType'           => 7,
                'lotteryTime'           => '',
                'lotteryIssue'          => '',
                'lotteryWeek'           => '',
                'time'                  => '21点30分'
            ],
        ];
        foreach ($arr as $k => $v) {
            $arr[$k]['lotteryTime']  = Redis::get('lottery_real_open_date_'.$v['lotteryType']);
            $arr[$k]['lotteryIssue'] = Redis::get('lottery_real_open_issue_'.$v['lotteryType']);
            $arr[$k]['lotteryWeek']  = $this->dayOfWeek($arr[$k]['lotteryTime']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $arr);
    }

    /**
     * 开奖号码遗漏
     * @param $zhengMaCounts
     * @param $init_arr
     * @param $issue
     * @return array
     */
    private function forgetNumber($zhengMaCounts, $init_arr, $issue): array
    {
        $zhengMa = array_keys($zhengMaCounts);// 1.拿号码
        $diff = array_diff($init_arr, $zhengMa);// 2.算差值
        $count=10;
        if (count($diff) > $count) {  // 3.算遗漏
            // 差值满足条数
            $zhengMaYiLou = array_slice($diff, 0, $count);
            $zhengMaYiLou = array_flip($zhengMaYiLou);
            $forget = $this->tongJi($zhengMaYiLou, $issue); // 特码遗漏出现次数最多
        } else {
            // 差值不满足条数
            $num = $count - count($diff);   // 需要从出现（开出）的正码中 拿出对应的号码放在遗漏里
            ksort($zhengMaCounts);
            asort($zhengMaCounts);
            $arr1 = array_slice($zhengMaCounts, 0, $num, true);  // 拿出的号码
            $zhengMaYiLou = array_flip($diff);
            $res1 = $this->tongJi($zhengMaYiLou, $issue);
            $res2 = $this->tongJi($arr1);
            $forget = array_merge_recursive($res1, $res2);
            $this->sortByKey($forget, 'number');
            $this->sortByKey($forget, 'count', false);
        }
        foreach ($forget as $k => $v) {
            if ( in_array($v['number'], $zhengMa)) {
                $forget[$k]['count'] = $issue-$v['count'];
            }
        }

        return $forget;
    }

    /**
     * 颜色遗漏
     * @param array $colorList
     * @param bool $forget
     * @param int $issue
     * @return array
     */
    private function forgetColor(array $colorList, bool $forget=false, int $issue=0): array
    {
        arsort($colorList);
        $data = [];
        foreach ($colorList as $k => $v) {
            if ($forget) {
                $data[$k]['count'] = $issue - $v;
            } else {
                $data[$k]['count'] = $v;
            }

            $data[$k]['key'] = $k;
            $data[$k]['value'] = $k==3 ? '绿波' : ($k==2 ? '蓝波' : '红波');
        }
        $data = array_values($data);
        $this->sortByKey($data, 'count', false);

        return $data;
    }

    /**
     * 尾数统计
     * @param array $tailList
     * @return array
     */
    private function tongJiTail(array $tailList): array
    {
        ksort($tailList);
        arsort($tailList);
        $count = 5;
        $tailList = array_slice($tailList, 0, $count, true);
        $data = [];
        foreach ($tailList as $k => $v) {
            $data[$k]['count'] = $v;
            $data[$k]['name'] = $k;
        }

        return array_values($data);
    }

    /**
     * 尾数遗漏
     * @param array $tailList
     * @return array
     */
    private function forgetTail(array $tailList, $issue): array
    {
        $new_values = array_keys($tailList);
        $range = range(1, 10);
        $diff = array_diff($range, $new_values);
        $count = 5;
        $data = [];
        if (count($diff) < $count) {
            $num = $count-count($diff);
            ksort($tailList);
            asort($tailList);
            $data1 = $data2 = [];
            foreach ($diff as $k => $v) {
                $data1[$k]['name'] = $v;
                $data1[$k]['count'] = $issue;
            }
            $list = array_slice($tailList, 0, $num, true);
            foreach ($list as $k => $v) {
                $data2[$k]['name'] = $k;
                $data2[$k]['count'] = $issue-$v;
            }

            $data = array_merge_recursive(array_values($data1), array_values($data2));
        } else {
            asort($diff);
            foreach ($diff as $k => $v) {
                $data[$k]['name'] = $v;
                $data[$k]['count'] = $issue;
            }

            $data = array_values(array_slice($data, 0, $count));
        }

        return $data;
    }

    /**
     * 生肖统计
     * @param array $wxList
     * @return array
     */
    private function tongJiSx(array $wxList): array
    {
        $data = [];
        arsort($wxList);
        foreach ($wxList as $k => $v) {
            $data[$k]['name'] = $k;
            $data[$k]['count'] = $v;
        }

        return array_values(array_slice($data, 0, 6));
    }

    /**
     * 生肖遗漏
     * @param array $wxList
     * @param $issue
     * @return array
     */
    private function forgetSx(array $wxList, $issue): array
    {
        $all_sx = array_merge($this->jiaqin, $this->yeshou);
        $wxData = array_keys($wxList);
        $diff = array_diff($all_sx, $wxData);
        $data = [];
        $count = 6;
        if (count($diff)>=$count) {
            foreach ($diff as $k => $v) {
                $data[$k]['name'] = $v;
                $data[$k]['count'] = $issue;
            }
            $data = array_values(array_slice($data, 0, $count));
        } else {
            $num = $count-count($diff);
            krsort($diff);

            krsort($wxList);
            arsort($wxList);
            $data1 = $data2 = [];
            foreach ($diff as $k => $v) {
                $data1[$k]['name'] = $v;
                $data1[$k]['count'] = $issue;
            }
            $list = array_slice(array_reverse($wxList), 0, $num);
            foreach ($list as $k => $v) {
                $data2[$k]['name'] = $k;
                $data2[$k]['count'] = $issue-$v;
            }
            $data = array_merge_recursive(array_values($data1), array_values($data2));
        }

        return $data;
    }

    /**
     * 开奖回放
     * @param $data
     * @return JsonResponse
     */
    public function video($data): JsonResponse
    {
        $whereArr['lotteryType'] = $data['lotteryType'] ?? 1;
        if (!empty($data['year']))
        {
            $whereArr['year'] = $data['year'];
            $cacheKey = 'lottery_video_' . $data['lotteryType'] . $data['year'];
        } else {
            $cacheKey = 'lottery_video_' . $data['lotteryType'];
        }
        $lotteryVideo = Cache::get($cacheKey);
        if (!$lotteryVideo) {
            $lotteryVideo = LotteryVideo::where($whereArr)
                ->orderByDesc('created_at')
                ->paginate(25)
                ->toArray();
            $expiresAt = Carbon::now()->addMinutes(10);
            Cache::put($cacheKey, $lotteryVideo, $expiresAt);
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page' => $lotteryVideo['last_page'],
            'current_page' => $lotteryVideo['current_page'],
            'total' => $lotteryVideo['total'],
            'list' => $lotteryVideo['data']
        ]);
    }

    /**
     * 彩种类型列表
     * @return JsonResponse
     */
    public function lottery(): JsonResponse
    {
        $list = LotterySet::query()
            ->orderBy('lotteryType')
            ->where('status', 1)
            ->get()->toArray();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $list);
    }
}
