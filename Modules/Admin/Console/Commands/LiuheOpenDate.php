<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Admin\Models\LiuheOpenDay;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class LiuheOpenDate extends Command
{

    private $_url = 'https://49208.com/unite49/h5/lottery/listLotteryDate';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:open-date';      // 每月第一天 00:05 执行一次

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '爬取六合开奖时间列表.';

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
        if (Redis::get('lottery_open_day_switch_closed_by_month_'.date('Y-m'))) {
            return ;
        }
        $this->type_1();
//        sleep(10);
//        $this->type_3();
//        sleep(10);
//        $this->type_4();
//        sleep(10);
        // 澳彩 老澳 台彩
        // 当月第一天
        $dayList = $this->dayListOfMonth();
        $days = [];
        foreach ($dayList as $k => $day) {
            $days[$k]['lotteryType'] = 5;
            $days[$k]['year'] = date('Y');
            $days[$k]['month'] = date('Y-m');
            $days[$k]['open_date'] = $day;
        }
        $this->writeDb($days);

        $days = [];
        foreach ($dayList as $k => $day) {
            $days[$k]['lotteryType'] = 2;
            $days[$k]['year'] = date('Y');
            $days[$k]['month'] = date('Y-m');
            $days[$k]['open_date'] = $day;
        }
        $this->writeDb($days);

        $days = [];
        foreach ($dayList as $k => $day) {
            $days[$k]['lotteryType'] = 3;
            $days[$k]['year'] = date('Y');
            $days[$k]['month'] = date('Y-m');
            $days[$k]['open_date'] = $day;
        }
        $this->writeDb($days);

        $days = [];
        foreach ($dayList as $k => $day) {
            $days[$k]['lotteryType'] = 6;
            $days[$k]['year'] = date('Y');
            $days[$k]['month'] = date('Y-m');
            $days[$k]['open_date'] = $day;
        }
        $this->writeDb($days);

        $days = [];
        foreach ($dayList as $k => $day) {
            $days[$k]['lotteryType'] = 7;
            $days[$k]['year'] = date('Y');
            $days[$k]['month'] = date('Y-m');
            $days[$k]['open_date'] = $day;
        }
        $this->writeDb($days);
    }

    public function type_1()
    {
        $response = Http::withOptions([
            'verify'=>false
        ])->withHeaders([
            'Lotterytype' => 1,
            'User-Agent'  => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36'
        ])->get($this->_url, [
            'cache_buster' => uniqid(), // 添加一个唯一的查询参数
        ]);
        if($response->status() != 200) {
            Log::error('命令【module:open-date】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
            exit('终止此次');
        }
        $res = json_decode($response->body(), true);
        $response->close();
        if($res['code'] == 10000 && !empty($res['data']['list'])) {
            $list = $res['data']['list'];
            $list1 = $this->dealData($list, 1);
            $this->writeDb($list1);
        }
    }

    public function type_3()
    {
        $response = Http::withOptions([
            'verify'=>false
        ])->withHeaders([
            'Lotterytype' => 3,
            'User-Agent'  => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36'
        ])->get($this->_url, [
            'cache_buster' => uniqid(), // 添加一个唯一的查询参数
        ]);
        if($response->status() != 200) {
            Log::error('命令【module:open-date】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
            exit('终止此次');
        }
        $res = json_decode($response->body(), true);
        $response->close();
        if($res['code'] == 10000 && !empty($res['data']['list'])) {
            $list = $res['data']['list'];
            $list1 = $this->dealData($list, 3);
            $this->writeDb($list1);
        }
    }

    public function type_4()
    {
        $response = Http::withOptions([
            'verify'=>false
        ])->withHeaders([
            'Lotterytype' => 4,
            'User-Agent'  => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Mobile Safari/537.36'
        ])->get($this->_url, [
            'cache_buster' => uniqid(), // 添加一个唯一的查询参数
        ]);
        if($response->status() != 200) {
            Log::error('命令【module:open-date】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
            exit('终止此次');
        }
        $res = json_decode($response->body(), true);
        $response->close();
        if($res['code'] == 10000 && !empty($res['data']['list'])) {
            $list = $res['data']['list'];
            $list1 = $this->dealData($list, 4);
            $this->writeDb($list1);
        }
    }

    public function dayListOfMonth()
    {
        $firstDayOfMonth = new \DateTime('first day of this month');
        $lastDayOfMonth = new \DateTime('last day of this month');

        $daysArray = array();

        $currentDay = $firstDayOfMonth;
        while ($currentDay <= $lastDayOfMonth) {
            $daysArray[] = $currentDay->format('Y-m-d');
            $currentDay->add(new \DateInterval('P1D')); // 将日期增加1天
        }

        return $daysArray;
    }

    public function dealData($lists, $lotteryType)
    {
        $data = [];
        foreach ($lists as $k => $list) {
            foreach ($list['dayList'] as $kk => $vv) {
                $data[$k][$kk]['year'] = Str::substr($list['month'], 0, 4);
                $data[$k][$kk]['month'] = $list['month'];
                $data[$k][$kk]['lotteryType'] = $lotteryType;
                $data[$k][$kk]['open_date'] = $vv;
            }
        }

        if (isset($data[1])) {
            $arr = array_merge($data[0], $data[1]);
        } else {
            $arr = $data[0];
        }

        return $arr;
    }

    public function writeDb($list)
    {
        try{
            LiuheOpenDay::query()->upsert($list, ['lotteryType', 'open_date'], ['updated_at']);
        }catch (QueryException $exception) {
            return ;
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['example', InputArgument::REQUIRED, 'An example argument.'],
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
