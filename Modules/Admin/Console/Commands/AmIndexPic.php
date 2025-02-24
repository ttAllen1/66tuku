<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\IndexPic;
use Modules\Api\Services\picture\PictureService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Symfony\Component\String\s;

class AmIndexPic extends Command
{
    static $sort_config = [
        'color'         => 1,
        'sort'          => 1
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
    protected $signature = 'module:am-index-pic';       // 理论上一次性执行即可

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将49图库的首页图片信息写库，方便调用.执行一次即可';

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
        foreach ($this->_pic_configs as $config) {

            foreach ([1, 2] as $v) {
                $pageNum = 1;
                while (true) {
                    $response = Http::withOptions([
                        'verify'=>false
                    ])->get(sprintf($config['url'], $v, $pageNum));
                    if ($response->status() != 200) {
                        Log::error('命令【module:am-index-pic】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                        exit('终止此次');
                    }
                    $res = json_decode($response->body(), true);

                    if ($res['code'] == 200 && isset($res['data']) && !empty($res['data']['data'])) {
                        $list = $res['data']['data'];
                        echo '正在执行 '.($v==1 ? '彩色' : '黑白').' 第'.$pageNum.'页数据'.PHP_EOL;
                    } else {
                        echo '执行完毕，共采集'.$pageNum.'页数据 ！'.PHP_EOL;
                        break;
                    }

                    $this->wash_data($list, $pageNum);
                    $pageNum++;
                }
            }
        }
    }

    public function wash_data($lists, $pageNum)
    {
        $data = [];
        if ($lists[0]['color'] != self::$sort_config['color']) {
            self::$sort_config['color'] = $lists[0]['color'];
            self::$sort_config['sort'] = 1;
        }

        foreach ($lists as $k => $list) {
            $data[$k]['lotteryType']        = 5;
            $data[$k]['pictureTypeId']      = substr($list['picture_id'], 7);
            $data[$k]['pictureName']        = $list['name'];
            $data[$k]['color']              = $list['color'];
            $data[$k]['sort']               = self::$sort_config['sort']++;
//            $images = (new PictureService())->getImageInfoWithOutHttp($list['image_path']);
            $data[$k]['width']              = 0;
            $data[$k]['height']             = 0;
            $data[$k]['created_at']         = date('Y-m-d');
        }
//        if ($pageNum==2) {
//            dd($data);
//        }
//        dd($data);
        try{
            IndexPic::query()->insert($data);
        }catch (\Exception $exception) {
            dd($data, $exception->getMessage());
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
