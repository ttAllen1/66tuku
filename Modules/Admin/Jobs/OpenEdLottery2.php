<?php

namespace Modules\Admin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\YearPic;

class OpenEdLottery2 implements ShouldQueue
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
            $this->update_year_issue();
        }catch (\Throwable $exception) {
            Log::error('error', ['error1'=>$exception->getMessage()]);
        }

    }

    /**
     * 自动更新期数
     * @return void
     */
    public function update_year_issue()
    {
        // 更新最新一期期号
        foreach ([3, 4, 5, 6, 7] as $v) {
            try{
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
                        Log::channel('_real_open')->info('彩种(新奥)_'.$v.'_最新一期自动写入YearPic表');
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
//                    Log::channel('_real_open')->info('彩种_'.$v.'_最新一期自动写入YearPic表');
                    }
                } else if ($v ==5 || $v ==7) {
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
                } else if ($v ==6) {
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
                    }
                }
            }catch (\Exception $exception) {
                continue;
            }

        }

    }

    public function failed(\Throwable $exception)
    {
        // 给用户发送失败通知, 等等...
        Log::error('error', ['error2'=>$exception->getMessage()]);
    }
}
