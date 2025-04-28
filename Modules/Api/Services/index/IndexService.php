<?php

namespace Modules\Api\Services\index;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function guess11()
    {
        // 家禽野兽
        // 家禽	牛	马	羊	鸡	狗	猪
        // 野兽	鼠	虎	兔	龙	蛇	猴

        // 男肖	鼠	牛	虎	龙	马	猴	狗
        // 女肖	兔	蛇	羊	鸡	猪

        // 天肖	牛	兔	龙	马	猴	猪
        // 地肖	鼠	虎	蛇	羊	鸡	狗

        // 春肖	虎	兔	龙
        // 夏肖	蛇	马	羊
        // 秋肖	猴	鸡	狗
        // 冬肖	鼠	牛	猪

        // 琴肖	兔	蛇	鸡
        // 棋肖	鼠	牛	狗
        // 书肖	虎	龙	马
        // 画肖	羊	猴	猪

        // 文肖：鼠，猪，鸡，羊，龙，兔
        // 武肖：虎，牛，狗，猴，马，蛇

        // 阴性:	鼠、龙、蛇、马、狗、猪
        // 阳性:	牛、虎、兔、羊、猴、鸡

        $type = [
            'dan-shuang'     => [
                [
                    'title' => '单双中特',
                    'sub_title' => '单数',
                    'num'   => 3,
                    'color' => '#FF0000',
                    'refer' => [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49],
                    'user_id'   => [
                        1, 2, 3
                    ]
                ],
                [
                    'title' => '单双中特',
                    'sub_title' => '双数',
                    'num'   => 4,
                    'color' => '#FF0000',
                    'refer' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48],
                    'user_id'   => [
                        4, 5, 6
                    ]
                ]
            ],
            'da-xiao'        => [
                [
                    'title' => '大小中特',
                    'sub_title' => '特码大',
                    'num'   => 3,
                    'color' => '#FF0000',
                    'refer' => [25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49],
                    'user_id'   => [
                        7, 8, 9
                    ]
                ],
                [
                    'title' => '大小中特',
                    'sub_title' => '特码小',
                    'num'   => 4,
                    'color' => '#FF0000',
                    'refer' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24],
                    'user_id'   => [
                        10, 11, 12
                    ]
                ]
            ],
            'bo-se'          => [
                [
                    'title' => '波色中特',
                    'sub_title' => '一波中特',
                    'num'   => 1,
                    'color' => '#800080',
                    'refer' => ['红', '蓝', '绿'],
                    'user_id'   => [
                        13, 14, 15
                    ]
                ],
                [
                    'title' => '波色中特',
                    'sub_title' => '双波中特',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['红', '蓝', '绿'],
                    'user_id'   => [
                        16, 17, 18
                    ]
                ],
            ],
            'wen-wu'          => [
                [
                    'title' => '文武中特',
                    'sub_title' => '文官',
                    'num'   => 2,
                    'color' => '#800080', // 鼠，猪，鸡，羊，龙，兔
                    'refer' => ['鼠', '猪', '鸡', '羊', '龙', '兔'],
                    'user_id'   => [
                        19, 20, 21
                    ]
                ],
                [
                    'title' => '文武中特',
                    'sub_title' => '武官',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['虎', '牛', '狗', '猴', '马', '蛇'],
                    'user_id'   => [
                        22, 23, 24
                    ]
                ],
            ],
            'tian-di'       => [
                [
                    'title' => '天地肖',
                    'sub_title' => '天肖',
                    'num'   => 2,
                    'color' => '#FFA500',
                    'refer' => ['牛', '兔', '龙', '马', '猴', '猪'],
                    'user_id'   => [
                        25, 26, 27
                    ]
                ],
                [
                    'title' => '天地肖',
                    'sub_title' => '地肖',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['鼠', '虎', '蛇', '羊', '鸡', '狗'],
                    'user_id'   => [
                        28, 29, 30
                    ]
                ]
            ],
            'nan-nv'        => [
                [
                    'title' => '男女肖',
                    'sub_title' => '男肖',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['鼠', '牛', '虎', '龙', '马', '猴', '狗'],
                    'user_id'   => [
                        31, 32, 33
                    ]
                ],
                [
                    'title' => '男女肖',
                    'sub_title' => '女肖',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['兔', '蛇', '羊', '鸡', '猪'],
                    'user_id'   => [
                        34, 35, 36
                    ]
                ]
            ],
            'yin-yang'        => [
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阴肖',
                    'num'   => 3,
                    'color' => '#FFG500',
                    'refer' => ['鼠', '龙', '蛇', '马', '狗', '猪'],
                    'user_id'   => [
                        37, 38, 39
                    ]
                ],
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阳肖',
                    'num'   => 2,
                    'color' => '#FFG500',
                    'refer' => ['牛', '虎', '兔', '羊', '猴', '鸡'],
                    'user_id'   => [
                        40, 41, 42
                    ]
                ]
            ],
            'qin-qi-shu-hua' => [
                [
                    'title' => '琴棋书画',
                    'sub_title' => '琴棋',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['兔', '蛇', '鸡', '鼠', '牛', '狗'],
                    'user_id'   => [
                        43, 44, 45
                    ]
                ],
                [
                    'title' => '琴棋书画',
                    'sub_title' => '书画',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['虎', '龙', '马', '羊', '猴', '猪'],
                    'user_id'   => [
                        46, 47, 48
                    ]
                ]
            ],
            'jia-qin-ye-shou' => [
                [
                    'title' => '家禽野兽',
                    'sub_title' => '家禽',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['牛', '马', '羊', '鸡', '狗', '猪'],
                    'user_id'   => [
                        49, 50, 51
                    ]
                ],
                [
                    'title' => '家禽野兽',
                    'sub_title' => '野兽',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['鼠', '虎', '兔', '龙', '蛇', '猴'],
                    'user_id'   => [
                        52, 53, 54
                    ]
                ]
            ],
            'chun-xia-qiu-dong'          => [
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '春',
                    'num'   => 2,
                    'color' => '#800080',
                    'refer' => ['虎', '兔', '龙'],
                    'user_id'   => [
                        55, 56, 57
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '夏',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['蛇', '马', '羊'],
                    'user_id'   => [
                        58, 59, 60
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '秋',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['猴', '鸡', '狗'],
                    'user_id'   => [
                        61, 62, 63
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '冬',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['鼠', '牛', '猪'],
                    'user_id'   => [
                        64, 65, 66
                    ]
                ],
            ],
        ];

        $lotteryTypes = [1];
        $year = date('Y');
        $date = date('Y-m-d H:i:s');
        foreach ($lotteryTypes as $lotteryType) {

            $nextIssue = (int)$this->getNextIssue($lotteryType);
            $lastIssue = $nextIssue-1;
            $actualTeNum = 12;
            foreach ($type as $key => $value) {
                foreach ($value as $k => $v) {
                    $data = [];
                    if ($v['sub_title'] == '单数') {
                        foreach ($v['user_id'] as $kk => $userId) {
                            $randomElements = collect($v['refer'])->random(3)->all();
                            $newLine = $nextIssue
                                . '期：<span style="color:' . $v['color'] . ';">'
                                . $v['sub_title']
                                . '</span>：'
                                . implode(',', $randomElements);

                            $data[$kk]['user_id'] = $userId;
                            $data[$kk]['lotteryType'] = $lotteryType;
                            $data[$kk]['thumbUpCount'] = 100;
                            $data[$kk]['views'] = 200;
                            $data[$kk]['issue'] = $nextIssue;
                            $data[$kk]['year'] = $year;
                            $data[$kk]['title'] = $v['title'];
                            $data[$kk]['content'] = $nextIssue . '期：<span style="color:'. $v['color']. ';">' . $v['sub_title'] . '</span>：' . implode(',', $randomElements) . PHP_EOL;
                            $data[$kk]['created_at'] = $date;
                            $existsIssue = DB::table('discusses')->where(['lotteryType' => $lotteryType, 'user_id' => $userId, 'year'=>$year, 'title' => $v['title']])->value('issue');
                            if (!$existsIssue) {
                                DB::table('discusses')->insert($data[$kk]);
                            } else {
                                if ($existsIssue != $nextIssue) {

                                    $record = DB::table('discusses')
                                        ->where(['lotteryType' => $lotteryType, 'user_id' => $userId, 'year'=>$year, 'title' => $v['title']])
                                        ->first();
                                    $oldContent = $record->content ?? '';
                                    $lines = array_filter(explode(PHP_EOL, trim($oldContent)));
                                    $evaluated = [];

                                    // —— 3. 给上一期那行加“对”或“错” ——
                                    foreach ($lines as $line) {
                                        if (strpos($line, "{$lastIssue}期：") !== false) {
                                            // 提取上一期预测的号码列表
                                            if (preg_match("/{$lastIssue}期：.*?：([\d,]+)/u", $line, $m)) {
                                                $preds = explode(',', $m[1]);
                                                $ok = in_array($actualTeNum, $preds);
                                                // 在末尾追加 对/错
                                                $line .= $ok ? ' 对' : ' 错';
                                            }
                                        }
                                        $evaluated[] = $line;
                                    }

                                    // 新预测行放在最前面，再加上已评估过的历史内容
                                    $updatedContent = $newLine
                                        . PHP_EOL
                                        . implode(PHP_EOL, $evaluated)
                                        . PHP_EOL;

                                    // 更新 如果期数相等的话 直接跳过 不更新
                                    unset($data[$kk]['thumbUpCount'], $data[$kk]['views'], $data[$kk]['created_at']);
                                    $data[$kk]['update_at'] = $date;

                                    DB::table('discusses')
                                        ->where('id', $record->id)
                                        ->update([
                                            'content'    => $updatedContent,
                                            'issue'      => $nextIssue,
                                            'updated_at' => $date,
                                        ]);
                                }
                            }
                        }

                    }
                }
            }
        }


    }

    public function guess1()
    {
        return ;
        // 家禽野兽
        // 家禽	牛	马	羊	鸡	狗	猪
        // 野兽	鼠	虎	兔	龙	蛇	猴

        // 男肖	鼠	牛	虎	龙	马	猴	狗
        // 女肖	兔	蛇	羊	鸡	猪

        // 天肖	牛	兔	龙	马	猴	猪
        // 地肖	鼠	虎	蛇	羊	鸡	狗

        // 春肖	虎	兔	龙
        // 夏肖	蛇	马	羊
        // 秋肖	猴	鸡	狗
        // 冬肖	鼠	牛	猪

        // 琴肖	兔	蛇	鸡
        // 棋肖	鼠	牛	狗
        // 书肖	虎	龙	马
        // 画肖	羊	猴	猪

        // 文肖：鼠，猪，鸡，羊，龙，兔
        // 武肖：虎，牛，狗，猴，马，蛇

        // 阴性:	鼠、龙、蛇、马、狗、猪
        // 阳性:	牛、虎、兔、羊、猴、鸡

        $type = [
            'dan-shuang'     => [
                [
                    'title' => '单双中特',
                    'sub_title' => '单数',
                    'num'   => 3,
                    'color' => '#FF0000',
                    'refer' => [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49],
                    'user_id'   => [
                        1, 2, 3
                    ]
                ],
                [
                    'title' => '单双中特',
                    'sub_title' => '双数',
                    'num'   => 4,
                    'color' => '#FF0000',
                    'refer' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48],
                    'user_id'   => [
                        4, 5, 6
                    ]
                ]
            ],
            'da-xiao'        => [
                [
                    'title' => '大小中特',
                    'sub_title' => '特码大',
                    'num'   => 3,
                    'color' => '#FF0000',
                    'refer' => [25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49],
                    'user_id'   => [
                        7, 8, 9
                    ]
                ],
                [
                    'title' => '大小中特',
                    'sub_title' => '特码小',
                    'num'   => 4,
                    'color' => '#FF0000',
                    'refer' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24],
                    'user_id'   => [
                        10, 11, 12
                    ]
                ]
            ],
            'bo-se'          => [
                [
                    'title' => '波色中特',
                    'sub_title' => '一波中特',
                    'num'   => 1,
                    'color' => '#800080',
                    'refer' => ['红', '蓝', '绿'],
                    'user_id'   => [
                        13, 14, 15
                    ]
                ],
                [
                    'title' => '波色中特',
                    'sub_title' => '双波中特',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['红', '蓝', '绿'],
                    'user_id'   => [
                        16, 17, 18
                    ]
                ],
            ],
            'wen-wu'          => [
                [
                    'title' => '文武中特',
                    'sub_title' => '文官',
                    'num'   => 2,
                    'color' => '#800080', // 鼠，猪，鸡，羊，龙，兔
                    'refer' => ['鼠', '猪', '鸡', '羊', '龙', '兔'],
                    'user_id'   => [
                        19, 20, 21
                    ]
                ],
                [
                    'title' => '文武中特',
                    'sub_title' => '武官',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['虎', '牛', '狗', '猴', '马', '蛇'],
                    'user_id'   => [
                        22, 23, 24
                    ]
                ],
            ],
            'tian-di'       => [
                [
                    'title' => '天地肖',
                    'sub_title' => '天肖',
                    'num'   => 2,
                    'color' => '#FFA500',
                    'refer' => ['牛', '兔', '龙', '马', '猴', '猪'],
                    'user_id'   => [
                        25, 26, 27
                    ]
                ],
                [
                    'title' => '天地肖',
                    'sub_title' => '地肖',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['鼠', '虎', '蛇', '羊', '鸡', '狗'],
                    'user_id'   => [
                        28, 29, 30
                    ]
                ]
            ],
            'nan-nv'        => [
                [
                    'title' => '男女肖',
                    'sub_title' => '男肖',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['鼠', '牛', '虎', '龙', '马', '猴', '狗'],
                    'user_id'   => [
                        31, 32, 33
                    ]
                ],
                [
                    'title' => '男女肖',
                    'sub_title' => '女肖',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['兔', '蛇', '羊', '鸡', '猪'],
                    'user_id'   => [
                        34, 35, 36
                    ]
                ]
            ],
            'yin-yang'        => [
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阴肖',
                    'num'   => 3,
                    'color' => '#FFG500',
                    'refer' => ['鼠', '龙', '蛇', '马', '狗', '猪'],
                    'user_id'   => [
                        37, 38, 39
                    ]
                ],
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阳肖',
                    'num'   => 2,
                    'color' => '#FFG500',
                    'refer' => ['牛', '虎', '兔', '羊', '猴', '鸡'],
                    'user_id'   => [
                        40, 41, 42
                    ]
                ]
            ],
            'qin-qi-shu-hua' => [
                [
                    'title' => '琴棋书画',
                    'sub_title' => '琴棋',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['兔', '蛇', '鸡', '鼠', '牛', '狗'],
                    'user_id'   => [
                        43, 44, 45
                    ]
                ],
                [
                    'title' => '琴棋书画',
                    'sub_title' => '书画',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['虎', '龙', '马', '羊', '猴', '猪'],
                    'user_id'   => [
                        46, 47, 48
                    ]
                ]
            ],
            'jia-qin-ye-shou' => [
                [
                    'title' => '家禽野兽',
                    'sub_title' => '家禽',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['牛', '马', '羊', '鸡', '狗', '猪'],
                    'user_id'   => [
                        49, 50, 51
                    ]
                ],
                [
                    'title' => '家禽野兽',
                    'sub_title' => '野兽',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['鼠', '虎', '兔', '龙', '蛇', '猴'],
                    'user_id'   => [
                        52, 53, 54
                    ]
                ]
            ],
            'chun-xia-qiu-dong'          => [
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '春',
                    'num'   => 2,
                    'color' => '#800080',
                    'refer' => ['虎', '兔', '龙'],
                    'user_id'   => [
                        55, 56, 57
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '夏',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['蛇', '马', '羊'],
                    'user_id'   => [
                        58, 59, 60
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '秋',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['猴', '鸡', '狗'],
                    'user_id'   => [
                        61, 62, 63
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '冬',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['鼠', '牛', '猪'],
                    'user_id'   => [
                        64, 65, 66
                    ]
                ],
            ],
        ];

        $lotteryTypes = [1];
        $year = date('Y');
        $date = date('Y-m-d H:i:s');
        foreach ($lotteryTypes as $lotteryType) {
            // 计算当前期和上一期
            $nextIssue = (int)$this->getNextIssue($lotteryType);
            $lastIssue = $nextIssue - 1;

            // 1. 从数据库读取上一期的 te_attr JSON
            $raw = DB::table('history_numbers')
                ->where('lotteryType', $lotteryType)
                ->where('year', $year)
                ->where('issue', str_pad($lastIssue, 3, '0', STR_PAD_LEFT))
                ->value('te_attr');
            // JSON 解码
            $actual = json_decode($raw, true) ?: [];

            // 2. 提取实际数据字段
            $actualNumber   = isset($actual['number'])   ? (int)$actual['number']   : null;
            $colorMap       = [1 => '红', 2 => '蓝', 3 => '绿'];
            $actualColor    = $colorMap[$actual['color']] ?? '';
            $actualZodiac   = $actual['shengXiao'] ?? '';
            $actualOddEven  = $actual['oddEven']   ?? '';
            $actualBigSmall = $actual['bigSmall']  ?? '';

            foreach ($type as $group) {
                foreach ($group as $v) {
                    foreach ($v['user_id'] as $userId) {
                        // 3. 生成下一期预测文本
                        $preds = collect($v['refer'])->random($v['num'])->all();
                        $newLine = "{$nextIssue}期：<span style=\"color:{$v['color']}\">{$v['sub_title']}</span>：" . implode(',', $preds);

                        // 4. 取出已有记录及内容行
                        $record = DB::table('discusses')
                            ->where(['lotteryType' => $lotteryType, 'user_id' => $userId, 'year'=>$year, 'title' => $v['title']])
                            ->first();
                        $oldContent = $record->content ?? '';
                        $lines = array_filter(explode(PHP_EOL, trim($oldContent)));

                        $evaluated = [];
                        // 5. 对上一期行追加“对/错”
                        foreach ($lines as $line) {
                            if (strpos($line, "{$lastIssue}期：") !== false) {
                                if (preg_match("/{$lastIssue}期：.*?：(.+)/u", $line, $m)) {
                                    $list = array_map('trim', explode(',', $m[1]));
                                    $ok = false;
                                    // 数字匹配
                                    if ($list && ctype_digit((string)$list[0])) {
                                        $ok = in_array($actualNumber, array_map('intval', $list), true);
                                    }
                                    // 波色匹配
                                    elseif (in_array($list[0], ['红','蓝','绿'], true)) {
                                        $ok = in_array($actualColor, $list, true);
                                    }
                                    // 单双匹配
                                    elseif (in_array($list[0], ['单','双'], true)) {
                                        $ok = ($actualOddEven === $list[0]);
                                    }
                                    // 大小匹配
                                    elseif (in_array($list[0], ['大','小'], true)) {
                                        $ok = ($actualBigSmall === $list[0]);
                                    }
                                    // 其余均当生肖匹配
                                    else {
                                        $ok = in_array($actualZodiac, $list, true);
                                    }
                                    $line .= $ok ? ' 对' : ' 错';
                                }
                            }
                            $evaluated[] = $line;
                        }

                        // 6. 拼接新内容，置顶新预测，保存历史
                        $updatedContent = $newLine . PHP_EOL . implode(PHP_EOL, $evaluated) . PHP_EOL;

                        // 7. 插入或更新数据库
                        if (! $record) {
                            DB::table('discusses')->insert([
                                'user_id'     => $userId,
                                'lotteryType' => $lotteryType,
                                'year'        => $year,
                                'title'       => $v['title'],
                                'content'     => $updatedContent,
                                'issue'       => $nextIssue,
                                'thumbUpCount'=> 100,
                                'views'       => 200,
                                'created_at'  => $date,
                                'updated_at'  => $date,
                            ]);
                        } elseif ($record->issue != $nextIssue) {
                            DB::table('discusses')
                                ->where('id', $record->id)
                                ->update([
                                    'content'    => $updatedContent,
                                    'issue'      => $nextIssue,
                                    'updated_at' => $date,
                                ]);
                        }
                    }
                }
            }
        }
    }


    /**
     * 根据特码号码获取对应生肖
     */
    protected function getZodiac(int $num): string
    {
        $zodiacs = ['鼠','牛','虎','兔','龙','蛇','马','羊','猴','鸡','狗','猪'];
        // 号码与生肖对应按 1→鼠,2→牛…12→猪,13→鼠…
        return $zodiacs[($num - 1) % 12];
    }

    /**
     * 根据特码号码获取对应波色（红/蓝/绿），以香港六合彩标准为例
     */
    protected function getColor(int $num): string
    {
        static $reds = [1,2,7,8,12,13,18,19,23,24,29,30,34,35,40,45,46];
        static $blues= [3,4,9,10,14,15,20,25,26,31,36,37,41,42,47,48];
        if (in_array($num, $reds, true))   return '红';
        if (in_array($num, $blues, true))  return '蓝';
        return '绿';
    }


    public function test($params)
    {
        dd($params);
    }
}
