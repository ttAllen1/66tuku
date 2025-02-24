<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Models\HistoryNumber;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SpiderNumbers extends Command
{
    private const XG_TYPE       = 1;
    private const AM_TYPE       = 2;
    private const TW_TYPE       = 3;
    private const XJP_TYPE      = 4;
    private const INIT_YEAR     = 2020;
    protected $_history = false;
    private $_need_spider_lottery_types = [SpiderNumbers::XG_TYPE, SpiderNumbers::AM_TYPE, SpiderNumbers::TW_TYPE, SpiderNumbers::XJP_TYPE];
    private $_spider_url = "https://api.xyhzbw.com/unite49/h5/lottery/search?pageNum=%d&lotteryType=%d&year=%d&sort=0";

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
//        $url = 'https://h5.49218009.com/unite49/h5/lottery/search?pageNum=1&lotteryType=1&year=2022&sort=0';
        try{
            $current_year = date('Y');

            foreach ($this->_need_spider_lottery_types as $type) {

                for ($year=self::INIT_YEAR; $year<=$current_year; $year++) {
                    $page_num = 1;
                    if (!$this->_history) {
                        if ($year != $current_year) {
                            continue;
                        }
                    }
                    while (true) {
                        $url = sprintf($this->_spider_url, $page_num, $type, $year);
                        $response = Http::withOptions([
                            'verify'=>false
                        ])->get($url);
                        $res = json_decode($response->body(), true);
                        if ($res['code'] != 10000 && $res['success']) {
                            throw new \Exception('爬虫出错，状态码不是1000！');
                        }
                        if (!isset($res['data']['recordList'])) {
                            echo '正在执行'.$year.'年 类型为：'.$type. ' 当前第'.$page_num.'页！注意：'.$year.'已执行完毕'.PHP_EOL;
                            break;
                        }

                        $this->wash_data($res);
                        echo '正在执行'.$year.'年 类型为：'.$type. ' 当前第'.$page_num.'页！'.PHP_EOL;
                        $page_num++;
                    }
                }
            }
        }catch (\Exception $exception) {
            dd('当前执行：'.$year.'年 '."lotteryType:".$type.' 页码：'.$page_num.' 错误原因：'.$exception->getMessage().' 错误行：'.$exception->getLine().' 文件： '.$exception->getFile());
        }
    }

    private function wash_data($res)
    {
        $data = [];
        $recordList = $res['data']['recordList'];
        $date = date('Y-m-d H:i:s');
        foreach ($recordList as $k => $v) {
            $data[$k]['year'] = $v['year'];
            $data[$k]['issue'] = $v['period']<10 ? '0'.$v['period'] : ($v['period']<100 ? '00'.$v['period'] : $v['period']);
            $data[$k]['lotteryType'] = $v['lotteryType'];
            $data[$k]['lotteryTime'] = $this->date_format($v['lotteryTime']);
            $data[$k]['lotteryWeek'] = $this->week_format($data[$k]['lotteryTime']);
            $data[$k]['number'] = implode(' ', array_column($v['numberList'], 'number'));
            $data[$k]['attr_sx'] = implode(' ', array_column($v['numberList'], 'shengXiao'));
            $data[$k]['attr_wx'] = implode(' ', array_column($v['numberList'], 'wuXing'));
            $data[$k]['attr_bs'] = implode(' ', array_column($v['numberList'], 'color'));
            $data[$k]['number_attr'] = json_encode($v['numberList']);
            $data[$k]['te_attr'] = json_encode($this->spi_data(array_slice($v['numberList'], -1, 1)[0]));
            $data[$k]['total_attr'] = json_encode($this->total_data(array_column($v['numberList'], 'number')));
            $data[$k]['created_at'] = $date;
        }

        $res = HistoryNumber::query()->upsert($data, ['year', 'issue', 'lotteryType']);

    }

    public function date_format($date)
    {
        $date = str_replace('年', '-', $date);
        $date = str_replace('月', '-', $date);
        $date = str_replace('日', '', $date);
        return $date;
    }

    public function week_format($date)
    {
        $week = date('w', strtotime($date));
        switch ($week){
            case 0:
                $w = '日';
                break;
            case 1:
                $w = '一';
                break;
            case 2:
                $w = '二';
                break;
            case 3:
                $w = '三';
                break;
            case 4:
                $w = '四';
                break;
            case 5:
                $w = '五';
                break;
            case 6:
            default:
                $w = '六';
                break;
        }

        return $w;
    }

    public function spi_data($numData)
    {
        $numData['oddEven'] = $numData['number'] % 2 == 0 ? '双' : '单';
        $numData['bigSmall'] = $numData['number'] >=25 ? '大' : '小';
        return $numData;
    }

    public function total_data($numData)
    {
        $data['total'] = array_sum($numData);
        $data['oddEven'] = $data['total'] % 2 == 0 ? '双' : '单';
        $data['bigSmall'] = $data['total'] >= 175 ? '大' : '小';
        return $data;
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
