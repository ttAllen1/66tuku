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
use Illuminate\Support\Str;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\NumberRecommend;
use Modules\Admin\Models\YearPic;
use Modules\Api\Models\Mystery;
use Modules\Api\Models\PicForecast;
use Modules\Api\Services\lottery\LotteryService;
use Modules\Common\Services\BaseService;

class OpenEdLottery5 implements ShouldQueue
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
            $this->am_mysterytips();
        }catch (\Throwable $exception) {
            Log::error('error', ['error5'=>$exception->getMessage()]);
        }

    }

    /**
     * 图片竞猜判断是否中奖
     * @return void
     */
    public function am_mysterytips()
    {
        try{
            $data = [];
            $realOpen  = Redis::get('real_open_5');
            $issue = (int)explode(',', $realOpen)[8];
            if (!Mystery::query()->where('year', $this->_year)
                ->where('lotteryType', 5)
                ->where('issue', $issue)
                ->exists()) {
                $res = Mystery::query()->select(['content'])->where('lotteryType', '<>', 5)->orderByRaw('RAND()')->take(1)->get();

                foreach ($res as $k => $content) {
                    $data[$k]['year'] = $this->_year;
                    $data[$k]['issue'] = $issue;
                    $data[$k]['lotteryType'] = 5;
                    $data[$k]['title'] = $this->_year.'年第'.$issue.'期六合彩';
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

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
