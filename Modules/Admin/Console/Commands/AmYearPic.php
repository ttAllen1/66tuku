<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\YearPic;
use Modules\Api\Services\picture\PictureService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Symfony\Component\String\s;

class AmYearPic extends Command
{
    static $sort_config = [
        'color'         => 1,
        'sort'          => 1
    ];

    public $_typeId = [];

    public $_aaa = [
        'list'=> [],
        'page'=>[],
        'k'=>[],
        'listId'=>[],
    ];

    protected $_pic_configs = [
        [
            'type'      => 5,
            'url'       => "https://jl.tukuapi.com/api/tuku_list?lottery_type=2&color=%d&page=%d&year=2023&is_new=1",
            'year'      => [2020, 2021, 2022, 2023],
        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:am-year-pic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将澳彩图片信息写入year_pic表，方便调用.执行一次即可';

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
        ini_set('memory_limit', '556M');
        foreach ($this->_pic_configs as $config) {

            foreach ([1, 2] as $v) {
                $pageNum = 1;
                while(true) {
                    $response = Http::withOptions([
                        'verify'=>false
                    ])->get(sprintf($config['url'], $v, $pageNum));
                    if ($response->status() != 200) {
                        Log::error('命令【module:am-year-pic】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                        exit('终止此次');
                    }
                    $res = json_decode($response->body(), true);
                    if ($res['code'] == 200 && isset($res['data']) && !empty($res['data']['data'])) {
                        $lists = $res['data']['data']; // 列表30条数据
                        $detailData = [];
                        foreach ($lists as $k => $list) {
                            $response = Http::withOptions([
                                'verify'=>false
                            ])->get(sprintf('https://api.6ctkapi.com/api/tuku_detail?id=%d&year=2023&issue=', $list['id']));
                            if ($response->status() != 200) {
                                Log::error('命令【module:am-year-pic】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                                exit('终止此次');
                            }
                            $data = json_decode($response->body(), true);
                            $detailData[$k] = $data['data'];
//                            $this->wash_data($detailData, $pageNum);
                            echo '正在执行 '.($v==1 ? '彩色' : '黑白').' 第'.$pageNum.'页数据的 ID 为 '.$list['id'].' 当前页剩余：'.(30-$k-1).PHP_EOL;
                        }

                        $this->wash_data($detailData, $pageNum);
                        echo '============='.($v==1 ? '彩色' : '黑白').' 第'.$pageNum.'页数据执行完毕'.PHP_EOL;
                    } else {
                        echo '<<<<<<<<<<<<<<<>>>>>>>>>>>'.($v==1 ? '彩色' : '黑白').'执行完毕，共采集'.$pageNum.'页数据 ！'.PHP_EOL;
                        break;
                    }
                    $pageNum++;
                }

            }
        }
    }

    public function wash_data($lists,  $pageNum)
    {
        $data = [];

        foreach ($lists as $k => $list) {
            unset($list['issue_list']);

            foreach ([2020, 2021, 2022, 2023] as $kk => $year) {
                $issueList = [];
                $data[$k][$kk]['pictureTypeId'] = substr($list['picture_id'], 7);
                $data[$k][$kk]['lotteryType'] = 5;
                $data[$k][$kk]['year'] = $year;
                $data[$k][$kk]['color'] = $list['color'];
                $data[$k][$kk]['pictureName'] = $list['name'];
                if ($year == 2020) {
                    $data[$k][$kk]['max_issue'] = 355;
                    for ($i=355; $i>322; $i--) {
                        $issueList[($i-1)] = '第'.$i.'期';
                    }
                } else if ($year == 2021 || $year == 2022) {
                    $data[$k][$kk]['max_issue'] = 365;
                    for ($i=365; $i>0; $i--) {
                        $issueList[($i-1)] = '第'.$i.'期';
                    }
                } else if ($year == 2023) {
                    $data[$k][$kk]['max_issue'] = 202;
                    for ($i=202; $i>0; $i--) {
                        $issueList[($i-1)] = '第'.$i.'期';
                    }
                }
                $issueList = array_values($issueList);
                $data[$k][$kk]['issues'] = $issueList;
                $data[$k][$kk]['keyword'] = $list['keyword'];
                $data[$k][$kk]['letter']  = $list['letter'];
            }
        }
//        dd($data);
        $arr = [];
        foreach ($data as $v) {
            foreach ($v as $vv) {
                $arr[] = $vv;
            }
        }
        foreach ($arr as $kkk => $v) {
            $arr[$kkk]['issues'] = json_encode($v['issues']);
            $arr[$kkk]['created_at']         = date('Y-m-d');
        }
        try{
            YearPic::query()->insert($arr);
        }catch (\Exception $exception) {
            dd( $pageNum, $exception->getMessage());
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
