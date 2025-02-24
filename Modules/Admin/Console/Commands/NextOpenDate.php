<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Swoole\Coroutine;
use Swoole\Process;
use Symfony\Component\Console\Input\InputOption;
use Swoole\Coroutine\Http\Client;
use function Co\run;

class NextOpenDate extends Command
{

    protected $_lotteryTypes = [1, 2, 3, 4];

    protected $_url = 'https://h5.49217004.com:8443/unite49/h5/index/uniteInfo?lotteryType=%d';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:next-open-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取五大彩种下一期开奖日期及期号.新服务器，首次执行';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->_lotteryTypes as $v) {
            $response = Http::withOptions([
                'verify'=>false
            ])->withHeaders([
//                'lotteryType' => $v
            ])->get(sprintf($this->_url, $v));
            if ($response->status() != 200) {
                Log::error('命令【module:next-open-date】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                exit('终止此次');
            }
            $res = json_decode($response->body(), true);
            if ($res['data']['lastLotteryRecord']) {
                Redis::set('lottery_real_open_date_'.$v, $res['data']['lastLotteryRecord']['nextLotteryTime']);
                Redis::set('lottery_real_open_issue_'.$v, $res['data']['lastLotteryRecord']['nextLotteryNumber']);
                if ($v == 2) {
                    Redis::set('lottery_real_open_date_5', $res['data']['lastLotteryRecord']['nextLotteryTime']);
                    Redis::set('lottery_real_open_issue_5', $res['data']['lastLotteryRecord']['nextLotteryNumber']);
                }
            }
        }
    }

    public function do($lotteryType, $year)
    {

    }

    public function db($data)
    {

    }

    /**
     * 判断当前彩种是否在开奖时间段
     * @param $i
     * @return bool
     */
    private function ifLotteryTime($i): bool
    {
        $date = Redis::get('lottery_real_open_date_'.$this->_url[$i]['lotteryType']);
        $start = $this->_url[$i]['start'];
        $end = $this->_url[$i]['end'];
        $time = time();
        if ( ($time >= strtotime($date.' '.$start)) && ($time <= strtotime($date.' '.$end)) ) {
            return true;
        }

        return false;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
        ];
    }
}
