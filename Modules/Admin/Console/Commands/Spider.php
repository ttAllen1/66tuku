<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Modules\Admin\Models\ImgPage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Spider extends Command
{
    public $_lotteryType = [
        [
            'type'  => 1,
            'page'  => 295
        ],
        [
            'type'  => 2,
            'page'  => 156
        ],
        [
            'type'  => 3,
            'page'  => 32
        ],
        [
            'type'  => 4,
            'page'  => 33
        ]
    ];
    static $_sort = 1;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:img-init';  // 暂时决定废弃  数据表好像被废弃

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '图库首页加载初始化图片.';

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
//        array:4 [
//            "dirname" => " https://tk5.sycccf.com:4949/m/col/3857"
//          "basename" => "xjpjrxq.jpg"
//          "extension" => "jpg"
//          "filename" => "xjpjrxq"
//        ];
        foreach ($this->_lotteryType as $value) {
            $header = [
                'lotterytype:'.$value['type'],
                'user-agent:Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
            ] ;
            for ($i=1; $i<=$value['page']; $i++) {
                $url = 'https://49208.com/unite49/h5/index/listPicture?pageNum='.$i;
                $res = curl_request($url, false,[], $header);
                if (!$res) {
                    throw new \Exception('爬虫出错');
                }
                $res = $res['body']['data']['list'];
                $date = date('Y-m-d H:i:s');
                $data = [];
                foreach ($res as $k => $v) {
                    $a = pathinfo($v['bigPictureUrl']);
                    $data[$k]['lotteryType'] = $value['type'];
                    $data[$k]['keyword'] = $a['filename'];
                    $data[$k]['pictureName'] = $v['pictureName'];
                    $data[$k]['pictureUrl'] = $v['pictureName'];
                    $data[$k]['number'] = explode('/', $a['dirname'])[5];
                    $data[$k]['sort'] = self::$_sort;
                    $data[$k]['created_at'] = $date;
                    self::$_sort++;
                }
                ImgPage::query()->insert($data);
                echo '正在执行第'.$i.'页数据，共'.$value['page'].'页。剩余 '.($value['page']-$i).'页'.PHP_EOL;
            }
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
