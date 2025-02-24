<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\IndexPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Symfony\Component\String\s;

class PicInfoAssociate extends Command
{
    static $sort_config = [
        'lotteryType'   => 1,
        'sort'          => 1
    ];

    protected $_pic_configs = [
        [
            'type'      => 1,
            'url'       => "https://api.xyhzbw.com/unite49/h5/index/listPicture?pageNum=%d",
            'header'    => ['lotteryType' => 1],
            'year'      => [2020, 2021, 2022, 2023],
        ],
        [
            'type'      => 2,
            'url'       => "https://api.xyhzbw.com/unite49/h5/index/listPicture?pageNum=%d",
            'header'    => ['lotteryType' => 2],
            'year'      => [2020, 2021, 2022, 2023],
        ],
        [
            'type'      => 3,
            'url'       => "https://api.xyhzbw.comunite49/h5/index/listPicture?pageNum=%d",
            'header'    => ['lotteryType' => 3],
            'year'      => [2020, 2021, 2022, 2023],
        ],
        [
            'type'      => 4,
            'url'       => "https://api.xyhzbw.com/unite49/h5/index/listPicture?pageNum=%d",
            'header'    => ['lotteryType' => 4],
            'year'      => [2020, 2021, 2022, 2023],
        ],
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:pic-info';       // 理论上一次性执行即可

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
            $pageNum = 1;
            $flag = true;

            while ($flag) {
                $response = Http::withOptions([
                    'verify'=>false
                ])->withHeaders([
                    'lotteryType' => $config['header']
                ])->get(sprintf($config['url'], $pageNum));
                if ($response->status() != 200) {
                    Log::error('命令【module:pic-info】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                    exit('终止此次');
                }
                $res = json_decode($response->body(), true);
                if ($res['code'] == 10000 && isset($res['data']) && isset($res['data']['list']) && !empty($res['data']['list'])) {
                    $list = $res['data']['list'];
                    if ($config['type']==1) {
                        echo "香港彩！正在插入 ".$list[0]['pictureName']."的第".$pageNum."页。预计280页。还剩".(280-$pageNum)."页".PHP_EOL;
                    } else if ($config['type']==2) {
                        echo "澳门彩！正在插入 ".$list[0]['pictureName']."的第".$pageNum."页。预计170页。还剩".(170-$pageNum)."页".PHP_EOL;
                    } else if ($config['type']==3) {
                        echo "台湾彩！正在插入 ".$list[0]['pictureName']."的第".$pageNum."页。预计22页。还剩".(22-$pageNum)."页".PHP_EOL;
                    } else {
                        echo "新加坡彩！正在插入 ".$list[0]['pictureName']."的第".$pageNum."页。预计23页。还剩".(23-$pageNum)."页".PHP_EOL;
                    }
                } else {
                    echo '图片: 一个大彩种采集完毕'.PHP_EOL;
                    break;
                }

                $this->wash_data($list);
                $pageNum++;
            }
        }
    }

    public function wash_data($lists)
    {
        $data = [];
        if ($lists[0]['lotteryType'] != self::$sort_config['lotteryType']) {
            self::$sort_config['lotteryType'] = $lists[0]['lotteryType'];
            self::$sort_config['sort'] = 1;
        }
        foreach ($lists as $k => $list) {
            $data[$k]['lotteryType']        = $list['lotteryType'];
            $data[$k]['pictureTypeId']      = $list['pictureTypeId'];
            $data[$k]['pictureName']        = $list['pictureName'];
            if($list['lotteryType'] ==3 || $list['lotteryType'] ==4) {
                $data[$k]['color']              = 1;        // 台彩 新彩 只有彩图
            } else {
                $data[$k]['color'] = strrpos($list['bigPictureUrl'], 'col') !== false ? 1 : 2;
            }
            $data[$k]['sort']               = self::$sort_config['sort']++;
            $data[$k]['width']              = $list['width'];
            $data[$k]['height']             = $list['height'];
            $data[$k]['created_at']         = date('Y-m-d');
        }

        IndexPic::query()->insert($data);
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
