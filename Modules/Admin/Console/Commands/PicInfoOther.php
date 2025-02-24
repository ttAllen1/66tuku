<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PicInfoOther extends Command
{
    protected $_history = false;        // true 初始化 加载全部历史信息 false 只在每天开奖后更新最大期数

    protected $_year_list = [2020, 2021, 2022, 2023];
    protected $_color_list = [1, 2];
    protected $_type_list = [1, 2, 3, 4];
    protected $_url = "https://api.xyhzbw.com/unite49/h5/index/search?year=%d&keyword=&color=%d";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:pic-info-other';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '开奖后应立即执行[自动开奖完成及执行]：补充信息：将每种图片的每年的最大期 max_issue keyword letter等信息插入.支持每日更新';

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
        $i = 1;
        $current_year = date('Y');
        foreach ($this->_type_list as $item) {
            foreach ($this->_year_list as $year) {
                if (!$this->_history && $year != $current_year) {
                    continue;
                }
                foreach ($this->_color_list as $color) {
                    $response = Http::withOptions([
                        'verify'=>false
                    ])->withHeaders([
                        'Lotterytype'   => $item
                    ])->get(sprintf($this->_url, $year, $color));
                    if ($response->status() != 200) {
                        Log::error('命令【module:pic-info-other】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                        exit('终止此次');
                    }
                    $res = json_decode($response->body(), true);
                    if ($res['code'] == 10000 && isset($res['data']) && isset($res['data']['list']) && !empty($res['data']['list'])) {
                        echo "{$i} 正在执行 ".$year."年 当前颜色：".($color == 1 ? '彩色':'黑白')." 类型：".$item." 此大类即将执行完毕！ 总数：".count($res['data']['list']).PHP_EOL;
                        $i++;
                    } else {
                        break;
                    }
                    $this->wash_data($res);
                }
            }

        }
    }
    public function handle_1()
    {
        IndexPic::query()
            ->select(['id', 'lotteryType', 'pictureTypeId'])
            ->orderBy('id', 'asc')
            ->chunk(100, function ($indexImgs) {
                foreach ($indexImgs as $indexImg) {

                    $response = Http::withOptions([
                        'verify'=>false
                    ])->get(sprintf($this->_url, $indexImg['pictureTypeId']));
                    if ($response->status() != 200) {
                        Log::error('命令【module:pic-info-other】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                        exit('终止此次');
                    }

                    $res = json_decode($response->body(), true);
                    if ($res['code'] == 10000 && isset($res['data']) && isset($res['data']['periodList'])) {

                        $list['index_pic_id']       = $indexImg['id'];
                        $list['year']               = $res['data']['year'];
                        $list['max_issue']             = $res['data']['period'];
//                        $list['periodList']         = $res['data']['periodList'];
                        $list['yearList']           = $res['data']['yearList'];
                        $this->wash_data($list);
                    }
                }
            }, 100);
    }

    public function wash_data($res)
    {
        $data = [];
        foreach ($res['data']['list'] as $k => $items) {
            $data[$k]['year']               = $res['data']['year'];
            $data[$k]['color']              = $res['data']['color'];
            $data[$k]['pictureTypeId']      = $items['pictureTypeId'];
            $data[$k]['lotteryType']        = $items['lotteryType'];
            $data[$k]['max_issue']          = $items['number'];
            $data[$k]['keyword']            = $items['keyword'];
            $data[$k]['letter']             = $items['letter'];
            $data[$k]['pictureName']        = $items['pictureName'];
            $data[$k]['created_at']         = date('Y-m-d');

        }

        if ($this->_history) {
            YearPic::query()->insert($data);
        } else {
            YearPic::query()->upsert($data, ['year', 'pictureTypeId'], ['max_issue']);
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
