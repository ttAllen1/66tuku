<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\IpView;
use Modules\Api\Models\IpViewUv;

class YesterdayViews implements ShouldQueue
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
            // 获取昨天的日期
            $yesterday = Carbon::yesterday();
            $count = IpView::query()->whereDate('created_at', '=', $yesterday)
                ->count();
            Redis::set('yesterday_view_access', $count); // pv

            $count1 = IpViewUv::query()->whereDate('created_at', '=', $yesterday)
                ->count();
            Redis::set('yesterday_view_access_uv', $count1); // uv

//            $count2 = IpView::query()->whereDate('created_at', '<', $yesterday)
//                ->count();
//            Redis::set('history_view_access', $count+$count2);
        }catch (\Throwable $exception) {

        }
    }

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
