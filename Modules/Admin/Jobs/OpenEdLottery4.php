<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\NumberRecommend;
use Modules\Admin\Models\YearPic;
use Modules\Api\Models\PicForecast;
use Modules\Api\Services\lottery\LotteryService;
use Modules\Common\Services\BaseService;

class OpenEdLottery4 implements ShouldQueue
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
            $this->forecast();
        }catch (\Throwable $exception) {
            Log::error('error', ['error4'=>$exception->getMessage()]);
        }

    }

    /**
     * 图片竞猜判断是否中奖
     * @return void
     */
    public function forecast()
    {
        try{
            foreach ([1, 2, 3, 4, 5, 6, 7] as $v) {
                if (!Redis::get('lottery_real_open_over_'.$v.'_with_'.date('Y-m-d'))) {
                    continue;
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
                $picForecast = PicForecast::query()
                    ->where('issue', $issue)
                    ->where('year', $this->_year)
                    ->where('lotteryType', $v)
                    ->where('is_check', 0)
                    ->select(['id', 'forecastTypeId', 'position', 'content'])
                    ->orderByDesc('created_at')
                    ->chunkById(100, function($userForecasts) use ($lotteryService, $historyNumber, $v, $issue) {
                        foreach ($userForecasts as $userForecast) {
                            $res = $lotteryService->base($historyNumber, $userForecast);
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

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
