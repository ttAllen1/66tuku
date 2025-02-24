<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SpiderNumbers1 extends Command
{
    private const XG_TYPE       = 1;
    private const AM_TYPE       = 2;
    private const TW_TYPE       = 3;
    private const XJP_TYPE      = 4;
    private const INIT_YEAR     = 2020;
    private $_need_spider_lottery_types = [SpiderNumbers1::XG_TYPE, SpiderNumbers1::AM_TYPE, SpiderNumbers1::TW_TYPE, SpiderNumbers1::XJP_TYPE];
    private $_spider_url = "https://h5.49218009.com/unite49/h5/lottery/search?pageNum=%d&lotteryType=%d&year=%d&sort=0";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:history-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取历史开奖信息和当前开奖信息.';

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
        $url = 'https://h5.49218009.com/unite49/h5/lottery/search?pageNum=1&lotteryType=1&year=2022&sort=0';
        $response = Http::withOptions([
            'verify'=>false
        ])
        ->get($url);

        $a = json_decode($response->body(), true);
        print_r ($a );exit;
        try{
            $current_year = date('Y');
            $pointer = 0;
            $header = [
                'lotterytype'=>1,
                'user-agent:Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
            ] ;
            foreach ($this->_need_spider_lottery_types as $type) {

                for ($year=self::INIT_YEAR; $year<=$current_year; $year++) {
                    $page_num = 1;
                    // page_num  lottery_type   year
                    while (true) {
                        $url = sprintf($this->_spider_url, $page_num, $type, $year);

                        $res = curl_request($url, false, [], $header);
                        if ($res['code'] != 200) {
                            throw new \Exception('爬虫出错，状态码不是200！');
                        }

                        if (!isset($res['data']['recordList'])) {
                            break;
                        }
                        $page_num++;
                        $this->wash_data($res);
                    }

                }
            }
        }catch (\Exception $exception) {
            dd('当前执行：'.$year.'年 '."lotteryType:".$type.' 页码：'.$page_num.' 错误原因：'.$exception->getMessage());
        }
    }

    private function wash_data($res)
    {
        dd(1);
        dd($res['data']['recordList']);
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
