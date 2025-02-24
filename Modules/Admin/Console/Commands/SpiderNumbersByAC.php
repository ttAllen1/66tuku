<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Models\HistoryNumber;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SpiderNumbersByAC extends Command
{
    protected $_history = false;
    private $_spider_url = "https://ls.kjkj.fit/kj";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:history-number-am';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集自己的澳门历史数据.一次性';

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
        try{
            for ($year=2020; $year<=2023; $year++) {
                $response = Http::post($this->_spider_url, [
                    'g' => '48am',
                    'y' => $year,
                ]);

                $res = json_decode($response->body(), true)['data'];
                $data = [];
                foreach ($res as $k => $v) {
                    $numArr = explode(',', $v['num']);
                    $shengxiaoArr = explode(',', $v['shengxiao']);
                    $wuxingArr = explode(',', $v['wuxing']);
                    if (count($numArr) != 7 || count($shengxiaoArr) != 7 || count($wuxingArr) != 7) {
                        continue;
                    }
                    $attr = (new BaseService())->get_attr_num($numArr);
                    $data[$k]['year'] = $year;
                    $data[$k]['issue'] = str_pad($v['qishu'], 3, 0, STR_PAD_LEFT);
                    $data[$k]['lotteryType'] = 5;
                    $data[$k]['lotteryTime'] = $v['date'];
                    $data[$k]['lotteryWeek'] = (new BaseService())->dayOfWeek($v['date']);
                    $data[$k]['number'] = str_replace(',', ' ', $v['num']);
                    $data[$k]['attr_sx'] = implode(' ', $attr['sx']);
                    $data[$k]['attr_wx'] = implode(' ', $attr['wx']);
                    $data[$k]['attr_bs'] = implode(' ', $attr['bs']);
                    $data[$k]['number_attr'] = json_encode($attr['number_attr']);
                    $data[$k]['te_attr'] = json_encode($attr['te_attr']);
                    $data[$k]['total_attr'] = json_encode($attr['total_attr']);
                    $data[$k]['created_at'] = date('Y-m-d H:i:s');
                    echo '正在执行 '.$year.' 年 '. $k .' 条数据'.PHP_EOL;
                }
                echo '正在执行 '.$year.' 年数据'.PHP_EOL;
                sort($data);
                $this->wash_data($data);

            }
        }catch (\Exception $exception) {
            dd(' 错误原因：'.$exception->getMessage().' 错误行：'.$exception->getLine().' 文件： '.$exception->getFile());
        }
    }

    private function wash_data($data)
    {

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
