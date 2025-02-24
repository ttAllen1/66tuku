<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\NumberRecommend;
use Modules\Admin\Models\YearPic;
use Modules\Api\Models\LiuheOpenDay;
use Modules\Api\Models\PicForecast;
use Modules\Api\Models\UserBet;
use Modules\Api\Services\lottery\LotteryService;
use Modules\Api\Services\test\TestService;
use Modules\Common\Services\BaseService;

class TestController extends BaseApiController
{
    protected $_year = NULL;

    protected const SX = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];

    protected $_assocNums = []; // 当前年生肖-号码对应关系

    public function __construct()
    {
        $this->_year = date('Y');
    }

    /**
     * 自动写入新开号码
     * @return void
     */
    protected function newLotteryNum()
    {
        $year = date('Y');
        foreach ([5] as $v) {
            try{
                if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
//                    continue;
                }
                $arr = Redis::get('real_open_'.$v);
                $arr = explode(',', $arr);
                // 插入最新一期开奖号码
                $numbers = $arr[1].' '.$arr[2].' '.$arr[3].' '.$arr[4].' '.$arr[5].' '.$arr[6].' '.$arr[7];
                $attr = $this->getAttrSx($numbers);
//                dd($attr);
                $previousDate = LiuheOpenDay::query()->where('lotteryType', $v)->where('open_date', '<=', date('Y-m-d'))->orderBy('open_date', 'desc')->first(['open_date']);
//                dd()
                if ($v==2) {
                    $issue = str_pad(str_replace($this->_year, '', $arr[0]), 3, 0, STR_PAD_LEFT);
                } else {
                    $issue = str_pad($arr[0], 3, 0, STR_PAD_LEFT);
                }
//                dd($year, $issue, $v, $previousDate->open_date);
                $res = DB::table('history_numbers')->updateOrInsert(
                    ['year'=> $year, 'issue'=> $issue,'lotteryType'=> $v,'lotteryTime'=> $previousDate->open_date],
                    [
                        'lotteryWeek'   => (new BaseService())->dayOfWeek($previousDate->open_date),
                        'number'        => $numbers,
                        'attr_sx'       => implode(' ', $attr['sx']),
                        'attr_wx'       => implode(' ', $attr['wx']),
                        'attr_bs'       => implode(' ', $attr['bs']),
                        'number_attr'   => json_encode($attr['number_attr']),
                        'te_attr'       => json_encode($attr['te_attr']),
                        'total_attr'    => json_encode($attr['total_attr']),
                        'created_at'    => date('Y-m-d H:i:s')
                    ]
                );

            }catch (\Exception $exception) {
//                Log::channel('_real_open_err')->info('彩种_'.$v.'_新开号码自动写入HistoryNumber表失败, 原因：'.$exception->getMessage());
            }
        }

    }

    /**
     * 自动更新期数
     * @return void
     */
    public function update_year_issue()
    {
        // 更新最新一期期号
        foreach ([1, 2, 3, 4, 6] as $v) {
            if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
                continue;
            }
            $arr = Redis::get('real_open_'.$v);
            $arr = explode(',', $arr);
            $res = YearPic::query()->where('year', $this->_year)->where('lotteryType', $v)->select(['max_issue', 'issues']);
            $firstModel = $res->firstOrFail();
            if ($firstModel->issues) {
                $issuesArr = json_decode($firstModel->issues, true);
                $issues = $issuesArr[0];
                $issues = ltrim($issues, '第');
                $issues = rtrim($issues, '期');
            } else {
                $issues = 0;
            }

            if ($v ==1) {
                // 更新最新期数
                $currentMaxIssue = ltrim($arr[8], 0);
                if ($currentMaxIssue != $issues) {
                    if ($currentMaxIssue>$issues) {
                        $issues++;
                        for ($i=$issues; $i<=$currentMaxIssue; $i++) {
                            array_unshift($issuesArr, '第'.$i.'期');
                        }
                    }
                    YearPic::query()->where('year', $this->_year)->where('lotteryType', $v)->update([
                        'max_issue'=>$currentMaxIssue, 'issues'=>json_encode($issuesArr)
                    ]);
//                    Log::channel('_real_open')->info('彩种_'.$v.'_最新一期自动写入YearPic表');
                }
            } else if ($v ==2) {
                $currentMaxIssue = str_replace($this->_year, '', $arr[8]);
                if ($currentMaxIssue != $issues) {
                    if ($currentMaxIssue>$issues) {
                        $issues++;
                        for ($i=$issues; $i<=$currentMaxIssue; $i++) {
                            array_unshift($issuesArr, '第'.$i.'期');
                        }
                    }
                    YearPic::query()->where('year', $this->_year)->where('lotteryType', $v)->update([
                        'max_issue'=>$currentMaxIssue, 'issues'=>json_encode($issuesArr)
                    ]);
//                    Log::channel('_real_open')->info('彩种_'.$v.'_最新一期自动写入YearPic表');
                }
            }  else if ($v ==3) {
                $currentMaxIssue = ltrim($arr[8], 0);
                if ($currentMaxIssue != $issues) {
                    if ($currentMaxIssue>$issues) {
                        $issues++;
                        for ($i=$issues; $i<=$currentMaxIssue; $i++) {
                            array_unshift($issuesArr, '第'.$i.'期');
                        }
                    }
                    YearPic::query()->where('year', $this->_year)->where('lotteryType', $v)->update([
                        'max_issue'=>$currentMaxIssue, 'issues'=>json_encode($issuesArr)
                    ]);
//                    Log::channel('_real_open')->info('彩种_'.$v.'_最新一期自动写入YearPic表');
                }
            } else if ($v ==4) {
                $currentMaxIssue = $arr[8];
                if ($currentMaxIssue != $issues) {
                    if ($currentMaxIssue>$issues) {
                        $issues++;
                        for ($i=$issues; $i<=$currentMaxIssue; $i++) {
                            array_unshift($issuesArr, '第'.$i.'期');
                        }
                    }
                    YearPic::query()->where('year', $this->_year)->where('lotteryType', $v)->update([
                        'max_issue'=>$currentMaxIssue, 'issues'=>json_encode($issuesArr)
                    ]);
                    Log::channel('_real_open')->info('彩种_'.$v.'_最新一期自动写入YearPic表');
                }
            } else if ($v ==5) {
                $currentMaxIssue = $arr[8];
                if ($currentMaxIssue != $issues) {
                    if ($currentMaxIssue>$issues) {
                        $issues++;
                        for ($i=$issues; $i<=$currentMaxIssue; $i++) {
                            array_unshift($issuesArr, '第'.$i.'期');
                        }
                    }
                    YearPic::query()->where('year', $this->_year)->where('lotteryType', $v)->update([
                        'max_issue'=>$currentMaxIssue, 'issues'=>json_encode($issuesArr)
                    ]);
//                    Log::channel('_real_open')->info('彩种_'.$v.'_最新一期自动写入YearPic表');
                }
            }
        }

    }

    /**
     * 自动写入推荐
     * @return void
     */
    public function recommend()
    {
        foreach ([3] as $v) {
            if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
//                continue;
            }
            $arr = Redis::get('real_open_' . $v);  // 当拿到这数据时， 开奖已经完成
            $arr = explode(',', $arr);
            // 上一期推荐是否中
            if ($v == 2) {
                $issue1 = ltrim(str_replace($this->_year, '', $arr[0]), 0);
            } else {
                $issue1 = ltrim($arr[0], 0);
            }
            $recommend = NumberRecommend::query()->where('year', $this->_year)->where('nine_is_win', -1)->where('issue', $issue1)->where('lotteryType', $v)->latest()->first();

            if ($recommend) {
                $recommendData = $recommend->toArray();
                try{
                    $history = HistoryNumber::query()->where('year', $this->_year)->where('lotteryType', $v)->where('issue', str_pad($recommendData['issue'], 3, 0, STR_PAD_LEFT))->select(['id', 'number', 'attr_sx'])->first();
                    if ($history) {
                        $history = $history->toArray();
                        $te_num = substr($history['number'], -2);
                        $te_sx = mb_substr($history['attr_sx'], -1);
                        $isWin                  = $this->getIsWin($te_sx, $recommendData['nine_xiao']);
                        $isWin['te_is_win']     = in_array($te_num, explode(' ', $recommendData['ten_ma'])) ? 1 : 0;
                        $isWin['history_id']    = $history['id'];
                        $res = $recommend->update($isWin);

                    } else {
                        Log::channel('_real_open_err')->info('彩种_'.$v.'_上一期_'.$issue1.'推荐是否命中失败，HistoryNumber的'.str_pad($recommendData['issue'], 3, 0, STR_PAD_LEFT).'期数不存在');
                    }
                } catch (\Exception $exception) {
                    Log::channel('_real_open_err')->info('彩种_'.$v.'_上一期_'.$issue1.'推荐是否命中失败，原因：'.$exception->getMessage());
                }
            }

            // 创建新一期推荐
            if ($v == 2) {
                $issue2 = ltrim(str_replace($this->_year, '', $arr[8]), 0);
            } else {
                $issue2 = ltrim($arr[8], 0);
            }
            if (!DB::table('number_recommends')->where('year', $this->_year)->where('issue', $issue2)->where('lotteryType', $v)->value('id')) {
                $number = [];
                $number['year'] = $this->_year;
                $number['issue'] = $issue2;
                $number['lotteryType'] = $v;
                $number['nine_xiao'] = $this->randStr(1);
                $number['six_xiao'] = Str::substr($number['nine_xiao'], 0, 11);
                $number['four_xiao'] = Str::substr($number['nine_xiao'], 0, 7);
                $number['one_xiao'] = Str::substr($number['nine_xiao'], 0, 1);
                $number['ten_ma'] = $this->randStr(2);
                $res = NumberRecommend::query()->insertOrIgnore($number, ['year', 'issue', 'lotteryType']);
                if ($res) {
                    Log::channel('_real_open')->info('彩种_'.$v.'_新一期_'.$issue2.'_推荐自动写入成功');
                } else {
                    Log::channel('_real_open_err')->info('彩种_'.$v.'_新一期_'.$issue2.'_推荐自动写入失败');
                }
            }

        }

    }

    /**
     * 图片竞猜判断是否中奖
     * @return void
     */
    public function forecast()
    {
        try{
            foreach ([5] as $v) {
                if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
//                    continue;
                }
                $arr = Redis::get('real_open_' . $v);  // 当拿到这数据时， 开奖已经完成
                $arr = explode(',', $arr);
                if ($v == 2) {
                    $issue = str_replace($this->_year, '', $arr[0]);
                } else {
                    $issue = ltrim($arr[0], 0);
                }

                $lotteryService = new LotteryService();
                $historyNumber = HistoryNumber::query()->orderByDesc('created_at')->where('lotteryType', $v)->where('issue', str_pad($issue, 3, 0, STR_PAD_LEFT))->select(['id', 'number', 'attr_sx', 'attr_wx', 'attr_bs'])->first();

                PicForecast::query()
                    ->where('issue', $issue)
                    ->where('year', $this->_year)
                    ->where('lotteryType', $v)
                    ->where('is_check', 0)
                    ->select(['id', 'forecastTypeId', 'position', 'content'])
                    ->orderByDesc('created_at')
                    ->chunkById(100, function($userForecasts) use ($lotteryService, $historyNumber, $v, $issue) {
                        foreach ($userForecasts as $userForecast) {

                            $res = $lotteryService->base($historyNumber, $userForecast);
//                            dd($res);
                            if ($res) {
                                $userForecast->update([
                                    'is_check'  => 1,
                                    'content'   => $res
                                ]);
                            } else {
                                Log::channel('_real_open_err')->error('图片竞猜是否中奖出错_'.$v.'_issue_'.$issue, ['error'=>"开奖逻辑出错"]);
                            }
                        }
                    });
            }
        }catch (\Exception $exception) {
            Log::channel('_real_open_err')->error('图片竞猜是否中奖出错_'.$v.'_issue_'.$issue, ['error'=>$exception->getMessage()]);
        }

    }

    public function bets(Request $request)
    {
        try{
            $year = $request->input('year', date("Y"));
            $open_year = $request->input('open_year', date("Y"));
            $lotteryType = $request->input('lotteryType', 0);
            $issue = $request->input('issue', 0);
            $issue = str_pad($issue, 3, 0, STR_PAD_LEFT);
            if (!$lotteryType || !$issue) {
                exit('请传参数');
            }
            // 当前开奖时间 期数 号码
            $numbersInfo = HistoryNumber::query()->where('year', $open_year)->where('lotteryType', $lotteryType)->where('issue', $issue)->select(['number', 'number_attr'])->firstOrFail()->toArray();
//            dd($numbersInfo);
//            $bets = $this->getBets($lotteryType, $issue, $numbersInfo);
            $bets = (new TestService())->bet($lotteryType, $issue, $numbersInfo);

            if (!$bets) {
                exit('该彩种该期数暂无投注信息');
            }
        }catch (\Exception $exception) {
            if ($exception instanceof ModelNotFoundException) {
                exit('该彩种该期数还没开奖');
            }
        }
    }



    public function getIsWin($te_sx, $nine_xiao)
    {
        $one = Str::substr($nine_xiao, 0, 1);
        $four = Str::substr($nine_xiao, 0, 7);
        $six = Str::substr($nine_xiao, 0, 11);
        if ($te_sx == $one) {
            return ['one_is_win'=>1, 'four_is_win'=>1, 'six_is_win'=>1, 'nine_is_win'=>1];
        } else if (in_array($te_sx, explode(' ', $four))) {
            return ['one_is_win'=>0, 'four_is_win'=>1, 'six_is_win'=>1, 'nine_is_win'=>1];
        } else if (in_array($te_sx, explode(' ', $six))) {
            return ['one_is_win'=>0, 'four_is_win'=>0, 'six_is_win'=>1, 'nine_is_win'=>1];
        } else if (in_array($te_sx, explode(' ', $nine_xiao))) {
            return ['one_is_win'=>0, 'four_is_win'=>0, 'six_is_win'=>0, 'nine_is_win'=>1];
        } else {
            return ['one_is_win'=>0, 'four_is_win'=>0, 'six_is_win'=>0, 'nine_is_win'=>0];
        }
    }

    public function randStr($type=1)
    {
        if ($type == 1) {
            $randomKeys = array_rand(self::SX, 9);
            $sx = [];
            foreach ($randomKeys  as $key) {
                $sx[] = self::SX[$key];
            }
            return implode(' ', $sx);
        } else {
            $numbers = [];
            while (count($numbers) < 10) {
                $num = mt_rand(1, 49);
                $randomNumber = str_pad($num, 2, "0", STR_PAD_LEFT); // 生成随机数，范围从1到49
                if (!in_array($randomNumber, $numbers)) {
                    $numbers[] = $randomNumber; // 将随机数添加到数组中
                }
            }

            return implode(' ', $numbers);
        }
    }

    private function getAttrSx($numbers)
    {
        return (new BaseService())->get_attr_num($numbers);
    }

}
