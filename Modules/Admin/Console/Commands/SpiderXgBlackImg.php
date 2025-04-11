<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SpiderXgBlackImg extends Command
{
    protected $_pic_configs = 'http://45.61.225.143:83/api/piclist';

    protected $signature = 'module:spider-xg-black';

    protected $description = '将49图库的首页图片信息写库，方便调用.执行一次即可';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try{
            DB::beginTransaction();
            //         lot_year_pics  year = 2025 and lotteryType = 1 and color = 2 => color = 3
            DB::table('year_pics')
                ->where('year', 2025)
                ->where('lotteryType', 1)
                ->where('color', 2)
                ->where('is_add', 0)
                ->update(['color' => 3, 'updated_at' => date('Y-m-d H:i:s')]);
            // lot_index_pics lotteryType = 1 and color = 2 and is_add = 0 => color = 3
            DB::table('index_pics')
                ->where('lotteryType', 1)
                ->where('color', 2)
                ->where('is_add', 0)
                ->update([
                    'color' => 3,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            $this->processData();
            DB::commit();
        }catch (\Exception $e) {
            DB::rollBack();
            echo 'Error: '. $e->getMessage();
        }

    }

    /**
     * 处理指定配置的图片数据。
     *
     * @param array $config 配置项
     */
    public function processData()
    {
        // 重试机制，避免请求失败
        $response = null;
        $retryCount = 3;
        while ($retryCount > 0) {
            $response = Http::withHeaders([
                'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
            ])->withOptions([
                'verify' => false
            ])->post($this->_pic_configs, [
                'color' => 1,
                'type'  => 'xg'
            ]);

            if ($response->status() == 200) {
                break;
            } else {
                Log::error('请求失败，状态码：' . $response->status());
                $retryCount--;
                if ($retryCount == 0) {
                    Log::error('请求失败，重试次数已达上限');
                    return;
                }
                sleep(1); // 小延迟再重试
            }
        }

        $res = json_decode($response->body(), true);

        // 检查返回数据是否有效
        if (empty($res['data'])) {
            Log::error('命令【module:am-index-pic】数据格式错误或为空');
            return;
        }

        $list = $res['data'];

        $yearData = [];
        $indexData = [];
        $startPictureTypeId = 600000;
        $date = date('Y-m-d H:i:s');
        foreach ($list as $k => $item) {
            $yearData[$k]['pictureTypeId'] = $startPictureTypeId + $k;
            $yearData[$k]['lotteryType'] = 1;
            $yearData[$k]['color'] = 2;
            $yearData[$k]['year'] = 2025;
            $yearData[$k]['pictureName'] =  $item['title'];
            $yearData[$k]['max_issue'] = 41;
            $yearData[$k]['issues'] = '["\u7b2c41\u671f","\u7b2c40\u671f","\u7b2c39\u671f","\u7b2c38\u671f","\u7b2c37\u671f","\u7b2c36\u671f","\u7b2c35\u671f","\u7b2c34\u671f","\u7b2c33\u671f","\u7b2c32\u671f","\u7b2c31\u671f","\u7b2c30\u671f","\u7b2c29\u671f","\u7b2c28\u671f","\u7b2c27\u671f","\u7b2c26\u671f","\u7b2c25\u671f","\u7b2c24\u671f","\u7b2c23\u671f","\u7b2c22\u671f","\u7b2c21\u671f","\u7b2c20\u671f","\u7b2c19\u671f","\u7b2c18\u671f","\u7b2c17\u671f","\u7b2c16\u671f","\u7b2c15\u671f","\u7b2c14\u671f","\u7b2c13\u671f","\u7b2c12\u671f","\u7b2c11\u671f","\u7b2c10\u671f","\u7b2c9\u671f","\u7b2c8\u671f","\u7b2c7\u671f","\u7b2c6\u671f","\u7b2c5\u671f","\u7b2c4\u671f","\u7b2c3\u671f","\u7b2c2\u671f","\u7b2c1\u671f"]';
            $yearData[$k]['keyword'] = basename($item['picname'], '.jpg');
            $yearData[$k]['letter'] = $item['szm'];
            $yearData[$k]['is_add'] = 0;
            $yearData[$k]['is_delete'] = 0;
            $yearData[$k]['created_at'] = $date;

            $indexData[$k]['pictureTypeId'] = $startPictureTypeId + $k;
            $indexData[$k]['lotteryType'] = 1;
            $indexData[$k]['color'] = 2;
            $indexData[$k]['pictureName'] = $item['title'];
            $indexData[$k]['sort'] = $item['sort'];
            $indexData[$k]['created_at'] = $date;
        }
        DB::table('year_pics')->insert($yearData);
        DB::table('index_pics')->insert($indexData);
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
