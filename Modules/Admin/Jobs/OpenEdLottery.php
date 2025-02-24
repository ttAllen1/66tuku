<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\HistoryNumber;
use Modules\Api\Models\LiuheOpenDay;
use Modules\Common\Services\BaseService;

class OpenEdLottery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $_year = NULL;
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
            Bus::chain([
                new OpenEdLottery2(),
                new OpenEdLottery3(),
                new OpenEdLottery4(),
                new OpenEdLottery5(),
            ])->dispatch();
            $this->newLotteryNum();
//            Log::info(111);
        }catch (\Throwable $exception) {
            Log::error('error', ['error1'=>$exception->getMessage()]);
        }
    }

    /**
     * 将新开的号码写入到历史表中
     * @return void
     */
    protected function newLotteryNum()
    {
        $year = date('Y');
        foreach ([1, 2, 3, 4, 5, 6, 7] as $v) {
            try{
                if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
                    continue;
                }
                $arr = Redis::get('real_open_'.$v);
                $arr = explode(',', $arr);
                // 插入最新一期开奖号码
                $numbers = $arr[1].' '.$arr[2].' '.$arr[3].' '.$arr[4].' '.$arr[5].' '.$arr[6].' '.$arr[7];

                $attr = $this->getAttrSx($numbers);
                $previousDate = LiuheOpenDay::query()->where('lotteryType', $v)->where('open_date', '<=', date('Y-m-d'))->orderBy('open_date', 'desc')->first(['open_date']);
                if ($v==2) {
                    $issue = str_pad(str_replace($this->_year, '', $arr[0]), 3, 0, STR_PAD_LEFT);
                } else {
                    $issue = str_pad($arr[0], 3, 0, STR_PAD_LEFT);
                }
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
                if ($res) {
//                    Log::channel('_real_open')->info('彩种_'.$v.'_新开号码自动写入HistoryNumber表');
                } else {
                    Log::channel('_real_open_err')->info('彩种_'.$v.'_新开号码自动写入HistoryNumber表失败！！！');
                }
            }catch (\Exception $exception) {
                Log::channel('_real_open_err')->info('彩种_'.$v.'_新开号码自动写入HistoryNumber表失败, 原因：'.$exception->getMessage());
            }
        }
    }

    private function getAttrSx($numbers)
    {
        return (new BaseService())->get_attr_num($numbers);
    }

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
