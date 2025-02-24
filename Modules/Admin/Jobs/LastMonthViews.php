<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\IpView;
use Modules\Api\Models\IpViewsCount;

class LastMonthViews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try{
            $lastMonth = Carbon::now()->subMonth();
            $lastYear = $lastMonth->year;
            $lastMonthNumber = $lastMonth->month;

            $dailyViewsLastMonth = IpView::query()->select(
                'year', 'month', 'day',
                DB::raw('COUNT(*) as views_count')
            )
                ->where('year', $lastYear)
                ->where('month', $lastMonthNumber)
                ->groupBy('day')
                ->get()->toArray();
            if ($dailyViewsLastMonth) {
                $data = [];
                foreach($dailyViewsLastMonth as $k => $v) {
                    $data[$k]['date'] = $v['year'].'-'.$v['month'].'-'.$v['day'];
                    $data[$k]['value'] = $v['views_count'];
                    $data[$k]['created_at'] = date('Y-m-d H:i:s');
                }
                IpViewsCount::query()->insertOrIgnore($data);
            }
        }catch (\Throwable $exception) {

        }
    }

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
