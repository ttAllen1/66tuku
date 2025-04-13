<?php
/**
 * 开奖历史管理服务
 * @Description
 */

namespace Modules\Admin\Services\lottery;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\LiuheNumber;
use Modules\Admin\Models\LiuheOpenDay;
use Modules\Admin\Models\NumberRecommend;
use Modules\Admin\Models\YearPic;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\Mystery;
use Modules\Api\Models\PicForecast;
use Modules\Api\Services\liuhe\LiuheService;
use Modules\Api\Services\lottery\LotteryService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Services\BaseService;

class HistoryService extends BaseApiService
{
    protected const SX = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];
    public function latest($data)
    {
        $lotteryType = $data['lotteryType'];
        $data = Redis::get('real_open_'.$lotteryType);
        $nextOpenDate = Redis::get('lottery_real_open_date_'.$lotteryType);
        if (!$nextOpenDate) {
            $nextOpenDate = LiuheOpenDay::query()->where('lotteryType', $lotteryType)->where('year', date('Y'))->where('open_date', '>', date('Y-m-d'))->orderBy('open_date')->value('open_date');
        }

        $nextIssue = Redis::get('lottery_real_open_issue_'.$lotteryType);
        if ($data) {
            $data = explode(',', $data);
        }
        return $this->apiSuccess('',[
            'data'          => $data,
            'nextOpenDate'  => $nextOpenDate,
            'nextIssue'     => $lotteryType==2 ? date('Y').$nextIssue : $nextIssue
        ]);
    }
    /**
     * @name 开奖历史列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $data['year'] = date('Y', strtotime($data['year']));

        $list = HistoryNumber::query()
            ->when($data['lotteryType'], function ($query) use ($data){
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->when($data['year'], function ($query) use ($data){
                $query->where('year', $data['year']);
            })
            ->when(isset($data['issue']), function ($query) use ($data){
                $query->where('issue', $data['issue']);
            })
            ->orderBy('year', 'desc')
            ->orderBy('issue', 'desc')
            ->paginate($data['limit'])
            ->toArray();
        if (!$list['data']) {
            $list = HistoryNumber::query()
                ->when($data['lotteryType'], function ($query) use ($data){
                    $query->where('lotteryType', $data['lotteryType']);
                })
                ->when($data['year'], function ($query) use ($data){
                    $query->where('year', $data['year']-1);
                })
                ->when(isset($data['issue']), function ($query) use ($data){
                    $query->where('issue', $data['issue']);
                })
                ->orderBy('year', 'desc')
                ->orderBy('issue', 'desc')
                ->paginate($data['limit'])
                ->toArray();
        }

        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        $data = $this->checkNums($data);
        return $this->commonCreate(HistoryNumber::query(),$data);
    }

    public function checkNums($data)
    {
        if (substr_count(trim($data['number'], ' '), ' ') != 6) {
            // 前端号码数字填写有误
            throw new \InvalidArgumentException('开奖号码需要用空格隔开');
        }
        $arr = explode(' ', $data['number']);
        foreach ($arr as $k => $v) {
            $data['attr_bs_arr'][$k] = $this->get_bose_num($v, 2);
        }
        $data['attr_bs'] = implode(' ', $data['attr_bs_arr']);

        $data['number_attr'] = $this->maps(['number'=>$data['number'], 'attr_sx'=>$data['attr_sx'], 'attr_wx'=>$data['attr_wx'], 'attr_bs'=>$data['attr_bs']]);
        $data['te_attr'] = json_encode($this->spi_data(array_slice($data['number_attr'], -1, 1)[0]));
        $data['total_attr'] = json_encode($this->total_data(array_column($data['number_attr'], 'number')));
        $data['number_attr'] = json_encode($data['number_attr']);
        unset($data['attr_bs_arr']);
        return $data;
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update(int $id,array $data){
        // 波色 要设置
        $data = $this->checkNums($data);

        return $this->commonUpdate(HistoryNumber::query(),$id,$data);
    }

    public function real_open($data)
    {  // 071,17,33,40,12,28,45,39,071,07,25,二,20点50分
        $next_open_time = date('m,d', strtotime($data['nextOpenTime'])); // 下一开奖日期
        $week = $this->dayOfWeek($data['nextOpenTime']);

        $number = $data['issue'].','.$data['one'].','.$data['two'].','.$data['three'].','.$data['four'].','.$data['five'].','.$data['six'].','.$data['seven'].','.$data['next_issue'].','.$next_open_time.','.$week.','.$data['time'];
        Redis::set('real_open_'.$data['lotteryType'], $number);
        // 写入json
        $writeData = [
            "data"          => $number,
            'lotteryType'   => $data['lotteryType'],
            "code"          => 1,
            "msg"           => "success"
        ];
        $writeData = json_encode($writeData);
        Storage::disk('public_open')->put($data['file_name'], $writeData);
        // 云存储1
        (new BaseService())->ALiOss($data['file_name'], $writeData);
        // 云存储1
        (new BaseService())->upload2S3($writeData, 'open_lottery', $data['file_name']);

        // 云存储2 v_格式
        $v_data = [];
        $v_data['Data'] = [];
        for ($i=1; $i<=7; $i++) {
            $numAttrs = $this->getNum($i, $data);
            $v_data['Data'][$i] = [
                "nim"    => $numAttrs['nim'],   // 五行
                "number" => $numAttrs['number'],
                "sx"     => $numAttrs['sx'],   // 生肖
                "color"  => $numAttrs['color']
            ];
            $v_data['Day'] = date("d", strtotime($data['nextOpenTime'])); // 下一期开奖日期
            $v_data['Year'] = date("Y", strtotime($data['nextOpenTime'])); // 下一期开奖年份
            $v_data['Moon'] = date("m", strtotime($data['nextOpenTime'])); // 下一期开奖月份
            $v_data['Nq'] = $data['next_issue']; // 下一期开奖期数
            $v_data['Qi'] = $data['issue']; // 当前开奖期数
            $v_data['Time'] = $data['time']; // 当前开奖时间
            $v_data['Week'] = $week; // 下一期开奖星期
            $v_data['Auto'] = false;

        }
//         写入json
        $writeData = [
            "data"          => $v_data,
            'lotteryType'   => $data['lotteryType'],
            "code"          => 1,
            "msg"           => "success"
        ];
//        dd($writeData);
        (new BaseService())->ALiOss('v_'.$data['file_name'], json_encode($writeData));
        (new BaseService())->upload2S3(json_encode($writeData), 'open_lottery', 'v_'.$data['file_name']);

        return $this->apiSuccess();
    }

    /**
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function manually($data): JsonResponse
    {
        $isSubmit = (bool)$data['isSubmit'];
        $lotteryTime = $data['lotteryTime'];

        if (!$isSubmit) {
            try{
                $form = json_decode($data['form'], true);
                $this->real_open($form);
                $lotteryNextTime = $form['nextOpenTime'];
                // 开奖完毕
                $arr = Redis::get('real_open_'.$data['lotteryType']);
                $arr = explode(',', $arr);
                // 下一期时间更新
                if (date('m-d') == '12-31') {
                    Redis::set('lottery_real_open_date_'.$data['lotteryType'], date('Y', strtotime('+1 year')).'-'.$arr[9].'-'.$arr[10]);
                } else {
                    Redis::set('lottery_real_open_date_'.$data['lotteryType'], date('Y').'-'.$arr[9].'-'.$arr[10]);
                }
                // 下一期期数更新
                if( $data['lotteryType']==2 ) {
                    Redis::set('lottery_real_open_issue_'.$data['lotteryType'], str_replace(date('Y'), '', $arr[8]));
                } else {
                    Redis::set('lottery_real_open_issue_'.$data['lotteryType'], $arr[8]);
                }
//            Redis::set('lottery_real_open_over_'.$data['lotteryType'].'_with_'.date('Y-m-d'), 1);
                Redis::setex('lottery_real_open_over_manually_'.$data['lotteryType'].'_with_'.$lotteryTime, 13*3600, 1);
                Redis::setex('lottery_real_open_over_'.$data['lotteryType'].'_with_'.$lotteryTime, 13*3600, 1);
                // 新增或修改开奖历史
//            Artisan::call('schedule:run');
                $this->newLotteryNum($data['lotteryType'], $lotteryTime, $lotteryNextTime);
                $this->update_year_issue($data['lotteryType']);
                $this->recommend($data['lotteryType']);
                $this->forecast($data['lotteryType']);
//                $this->am_mysterytips();
                $this->forecast_bet(date('Y'), $data['lotteryType'], str_pad(str_replace(date('Y'), '', $arr[0]), 3, 0, STR_PAD_LEFT));
            }catch (\Exception $exception) {
                return $this->apiError($exception->getMessage());
            }
        }

        return $this->apiSuccess();
    }

    public function getNum($i, $data)
    {
//        $rData = Redis::get(date("Y")."_num_attrs");
//        $rData1 = json_decode($rData, true);
//        dd($rData, $rData1);
//        $rData = LiuheNumber::query()->where('year', date("Y"))->get()->toArray();
        $rData = LiuheNumber::query()->where('year', 2025)->get()->toArray();
        $indexedArray = [];
        foreach ($rData as $item) {
            $indexedArray[$item['number']] = $item;
        }
//        dd($i, $data, $indexedArray);
        switch ($i) {
            case 1:
                $number = $data['one'];
                break;
            case 2:
                $number = $data['two'];
                break;
            case 3:
                $number = $data['three'];
                break;
            case 4:
                $number = $data['four'];
                break;
            case 5:
                $number = $data['five'];
                break;
            case 6:
                $number = $data['six'];
                break;
            case 7:
                $number = $data['seven'];
                break;
            default:
                $number = $i;
                break;
        }
        $sx = $indexedArray[$number]['zodiac'] ?? '-';
        if (!empty($indexedArray[$number]['bose'])) {
            $color = ($indexedArray[$number]['bose'] == '绿' ? 'green' : ($indexedArray[$number]['bose'] == '蓝' ? 'blue' : 'red'));
        } else {
            $color = '-';
        }
        $nim = $indexedArray[$number]['five_elements'] ?? '-';

        return ['number'=>$number, 'sx'=>$sx, 'color'=>$color, 'nim'=>$nim];
    }

    /**
     * 将新开的号码写入到历史表中
     * @throws CustomException
     */
    private function newLotteryNum($lotteryType, $lotteryTime, $lotteryNextTime): void
    {
        $year = date('Y');
        try{
            if (!Redis::get('lottery_real_open_over_manually_'.$lotteryType.'_with_'.$lotteryTime)) {
//                return;
            }
            $arr = Redis::get('real_open_'.$lotteryType);
            $arr = explode(',', $arr);
//            dd($arr);
            // 插入最新一期开奖号码
            $numbers = $arr[1].' '.$arr[2].' '.$arr[3].' '.$arr[4].' '.$arr[5].' '.$arr[6].' '.$arr[7];

            $attr = (new BaseService())->get_attr_num($numbers);
//            $previousDate = \Modules\Api\Models\LiuheOpenDay::query()->where('lotteryType', $lotteryType)->where('open_date', '<=', date('Y-m-d'))->orderBy('open_date', 'desc')->first(['open_date']);
//            dd($previousDate, $lotteryTime, $lotteryNextTime);
            if ($lotteryType==2) {
                $issue = str_pad(str_replace($year, '', $arr[0]), 3, 0, STR_PAD_LEFT);
            } else {
                $issue = str_pad($arr[0], 3, 0, STR_PAD_LEFT);
            }
            $res = DB::table('history_numbers')->updateOrInsert(
                ['year'=> $year, 'issue'=> $issue,'lotteryType'=> $lotteryType,'lotteryTime'=> $lotteryTime],
                [
                    'lotteryWeek'   => (new BaseService())->dayOfWeek($lotteryTime),
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
            return;
        }catch (\Exception $exception) {
            throw new CustomException(['message'=>'开奖失败：'.$exception->getMessage()]);
        }
    }

    /**
     * 自动更新期数
     * @return void
     * @throws CustomException
     */
    public function update_year_issue($lotteryType)
    {
        try{
            $year = date('Y');
            // 更新最新一期期号
            if (!Redis::get('lottery_real_open_over_manually_'.$lotteryType.'_with_'.date('Y-m-d'))) {
//                return ;
            }
            $arr = $this->getLotteryNumByRedis($lotteryType);
            $res = YearPic::query()
                ->when($lotteryType==2, function($query) {
                    $query->where('is_add', '<>', 0)->where('color', 2);
                })
                ->when($lotteryType==1, function($query) {
                    $query->where('color', 2)->where('is_add', '<>', 0);
                })
                ->where('year', $year)->where('lotteryType', $lotteryType)->select(['max_issue', 'issues']);
            $firstModel = $res->firstOrFail();
            $issuesArr = [];
            if ($firstModel->issues) {
                $issuesArr = json_decode($firstModel->issues, true);
                $issues = $issuesArr[0];
                $issues = ltrim($issues, '第');
                $issues = rtrim($issues, '期');
            } else {
                $issues = 0;
            }

            $currentMaxIssue = ltrim(Redis::get('lottery_real_open_issue_'.$lotteryType), 0);
            if ($lotteryType ==2) {
                $currentMaxIssue = str_replace($year, '', $currentMaxIssue);
            }
            if ($currentMaxIssue != $firstModel->max_issue) {
                YearPic::query()->where('year', $year)->where('lotteryType', $lotteryType)->update([
                    'max_issue'=>$currentMaxIssue
                ]);
            }
            if ($currentMaxIssue != $issues) {
                if ($currentMaxIssue>$issues) {
                    $issues++;
                    for ($i=$issues; $i<=$currentMaxIssue; $i++) {
                        array_unshift($issuesArr, '第'.$i.'期');
                    }
                }
                YearPic::query()->where('year', $year)->where('lotteryType', $lotteryType)->update([
                    'max_issue'=>$currentMaxIssue, 'issues'=>json_encode($issuesArr)
                ]);
            }
            return ;
        }catch (\Exception $exception) {
            if ($exception instanceof ModelNotFoundException) {
                return ;
            }
            throw new CustomException(['message'=>'最新期更新失败：'.$exception->getMessage()]);
        }


    }

    /**
     * 自动写入推荐
     * @return void
     */
    public function recommend($lotteryType)
    {
        $year = date('Y');
        if (!Redis::get('lottery_real_open_over_manually_'.$lotteryType.'_with_'.date('Y-m-d'))) {
//            return ;
        }
        $arr = Redis::get('real_open_' . $lotteryType);  // 当拿到这数据时， 开奖已经完成
        $arr = explode(',', $arr);
        // 上一期推荐是否中
        if ($lotteryType == 2) {
            $issue1 = ltrim(str_replace($year, '', $arr[0]), 0);
        } else {
            $issue1 = ltrim($arr[0], 0);
        }
        $recommend = NumberRecommend::query()
            ->where('year', $year)
            ->where('nine_is_win', -1)
            ->where('issue', $issue1)
            ->where('lotteryType', $lotteryType)
            ->latest()->first();
        if ($recommend) {
            $recommendData = $recommend->toArray();
            try{
                $history = HistoryNumber::query()
                    ->where('year', $year)
                    ->where('lotteryType', $lotteryType)
                    ->where('issue', str_pad($recommendData['issue'], 3, 0, STR_PAD_LEFT))
                    ->select(['id', 'number', 'attr_sx'])->first();
                if ($history) {
                    $history = $history->toArray();
                    $te_num = substr($history['number'], -2);
                    $te_sx = mb_substr($history['attr_sx'], -1);
                    $isWin                  = $this->getIsWin($te_sx, $recommendData['nine_xiao']);
                    $isWin['te_is_win']     = in_array($te_num, explode(' ', $recommendData['ten_ma'])) ? 1 : 0;
                    $isWin['history_id']    = $history['id'];
                    $res = $recommend->update($isWin);

                }
            } catch (\Exception $exception) {
            }
        }

        // 创建新一期推荐
        if ($lotteryType == 2) {
            $issue2 = ltrim(str_replace($year, '', $arr[8]), 0);
        } else {
            $issue2 = ltrim($arr[8], 0);
        }
        if (!DB::table('number_recommends')->where('year', $year)->where('issue', $issue2)->where('lotteryType', $lotteryType)->value('id')) {
            $number = [];
            $number['year'] = $year;
            $number['issue'] = $issue2;
            $number['lotteryType'] = $lotteryType;
            $number['nine_xiao'] = $this->randStr(1);
            $number['six_xiao'] = Str::substr($number['nine_xiao'], 0, 11);
            $number['four_xiao'] = Str::substr($number['nine_xiao'], 0, 7);
            $number['one_xiao'] = Str::substr($number['nine_xiao'], 0, 1);
            $number['ten_ma'] = $this->randStr(2);
            $number['created_at'] = date('Y-m-d H:i:s');
            $res = NumberRecommend::query()->insertOrIgnore($number);
        }
    }

    /**
     * 图片竞猜判断是否中奖
     * @return void
     */
    public function forecast($lotteryType)
    {
        $year = date('Y');
        try{
            if (!Redis::get('lottery_real_open_over_manually_'.$lotteryType.'_with_'.date('Y-m-d'))) {
//                return ;
            }
            $arr = Redis::get('real_open_' . $lotteryType);  // 当拿到这数据时， 开奖已经完成
            $arr = explode(',', $arr);
            if ($lotteryType == 2) {
                $issue = str_replace($year, '', $arr[0]);
            } else {
                $issue = ltrim($arr[0], 0);
            }
            $lotteryService = new LotteryService();
            $historyNumber = HistoryNumber::query()->orderByDesc('created_at')->where('lotteryType', $lotteryType)->where('issue', str_pad($issue, 3, 0, STR_PAD_LEFT))->select(['id', 'number', 'attr_sx', 'attr_wx', 'attr_bs'])->first();
            $picForecast = PicForecast::query()
                ->where('issue', $issue)
                ->where('year', $year)
                ->where('lotteryType', $lotteryType)
                ->where('is_check', 0)
                ->select(['id', 'forecastTypeId', 'position', 'content'])
                ->orderByDesc('created_at')
                ->chunkById(100, function($userForecasts) use ($lotteryService, $historyNumber, $lotteryType, $issue) {
                    foreach ($userForecasts as $userForecast) {
                        $res = $lotteryService->base($historyNumber, $userForecast);
                        if ($res) {
                            $userForecast->update([
                                'is_check'  => 1,
                                'content'   => $res
                            ]);
                        } else {
                        }
                    }
                });
        }catch (\Exception $exception) {
        }

    }

    /**
     * 图片竞猜判断是否中奖
     * @return void
     */
    public function am_mysterytips()
    {
        $year = date('Y');
        try{
            $data = [];
            $realOpen  = Redis::get('real_open_5');
            $issue = (int)explode(',', $realOpen)[8];
            if (!Mystery::query()->where('year', $year)
                ->where('lotteryType', 5)
                ->where('issue', $issue)
                ->exists()) {
                $res = Mystery::query()->select(['content'])->where('lotteryType', '<>', 5)->orderByRaw('RAND()')->take(1)->get();

                foreach ($res as $k => $content) {
                    $data[$k]['year'] = $year;
                    $data[$k]['issue'] = $issue;
                    $data[$k]['lotteryType'] = 5;
                    $data[$k]['title'] = $year.'年第'.$issue.'期六合彩';
                    $data[$k]['content'] = json_encode($content['content']);
                    $data[$k]['created_at'] = date('Y-m-d H:i:s');
                }

                DB::table('mysteries')
                    ->upsert($data, ['year', 'lotteryType', 'issue']);
            }
        }catch (\Exception $exception) {
            Log::channel('_real_open_err')->error('澳门幽默竞猜出错_issue_'.$issue, ['error'=>$exception->getMessage()]);
        }

    }

    public function forecast_bet($open_year, $lotteryType, $issue)
    {
        $numbersInfo = HistoryNumber::query()->where('year', $open_year)->where('lotteryType', $lotteryType)->where('issue', $issue)->select(['number', 'number_attr'])->firstOrFail()->toArray();
//            dd($numbersInfo);
//            $bets = $this->getBets($lotteryType, $issue, $numbersInfo);
        $bets = $this->getBets($lotteryType, $issue, $numbersInfo);
    }

    private function getLotteryNumByRedis($lotteryType)
    {
        $arr = Redis::get('real_open_'.$lotteryType);
        return explode(',', $arr);
    }

    public function getIsWin($te_sx, $nine_xiao): array
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

    public function randStr($type=1): string
    {
        if ($type == 1) {
            $randomKeys = array_rand(self::SX, 9);
            $sx = [];
            foreach ($randomKeys  as $key) {
                $sx[] = self::SX[$key];
            }
            shuffle($sx);
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

}
