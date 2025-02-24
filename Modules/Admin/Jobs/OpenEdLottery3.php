<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\NumberRecommend;

class OpenEdLottery3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $_year = NULL;

    protected const SX = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_year = date('Y');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            $this->recommend();
        }catch (\Throwable $exception) {
            Log::error('error', ['error1'=>$exception->getMessage()]);
        }

    }

    /**
     * 自动写入推荐
     * @return void
     */
    public function recommend()
    {
        foreach ([1, 2, 3, 4, 6, 7] as $v) {
            if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
                continue;
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
                        if ($res) {
//                            Log::channel('_real_open')->info('彩种_'.$v.'_上一期_'.$issue1.'_推荐自动写入是否命中');
                        } else {
                            Log::channel('_real_open_err')->info('彩种_'.$v.'_上一期_'.$issue1.'推荐是否命中失败，原因：update执行失败');
                        }
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
                $number['created_at'] = date('Y-m-d H:i:s');
                $res = NumberRecommend::query()->insertOrIgnore($number);
                if ($res) {
//                    Redis::set('Lottery_new_recommend_write_to_'.date('Y-m-d').'_with_'.$v, true);
//                    Log::channel('_real_open')->info('彩种_'.$v.'_新一期_'.$issue2.'_推荐自动写入成功');
                } else {
                    Log::channel('_real_open_err')->info('彩种_'.$v.'_新一期_'.$issue2.'_推荐自动写入失败');
                }
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

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
