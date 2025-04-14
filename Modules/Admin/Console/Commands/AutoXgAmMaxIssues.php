<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AutoXgAmMaxIssues extends Command
{
    protected $_pic_configs = [
        [
            'type'      => 1,
            'color'     => 1,
            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=1",
        ],
        //        [
        //            'type'      => 1,
        //            'color'     => 2,
        //            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=2",
        //        ],
        [
            'type'      => 2,
            'color'     => 1,
            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=1",
        ],
        [
            'type'      => 2,
            'color'     => 2,
            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=2",
        ]
    ];

    protected $signature = 'module:auto-xg-am-max-issue';

    protected $description = '将49图库的首页图片信息写库，方便调用.执行一次即可';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $h = date('H');
        $i = date('i');
        if ($h == 21 && $i <= 40) {
//            Log::info('当前时间为00:00，跳过处理');
            return;
        }
        foreach ($this->_pic_configs as $v) {
            // 开始进行数据抓取和处理
            $this->processData($v);
        }
    }

    /**
     * 处理指定配置的图片数据。
     *
     * @param array $config 配置项
     */
    public function processData($config)
    {
        $response = Http::withHeaders([
            'LotteryType' => $config['type'],
            'Connection' => 'close', // 关键头
            'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
        ])->withOptions([
            'verify' => false,
        ])->get($config['url']);

        $res = json_decode($response->body(), true);

        // 检查返回数据是否有效
        if ($res['code'] != 10000 || empty($res['data']['list'])) {
            Log::error('命令【module:am-index-pic】数据格式错误或为空');
            return;
        }

        $list = $res['data']['list'];
//        dd($list);
        DB::beginTransaction();
        try{
            foreach ($list as $k => $item) {
                $info = (array)DB::table('year_pics')
                    ->where('year', $res['data']['year'])
                    ->where('color', $item['color'])
                    ->where('keyword', $item['keyword'])
                    ->where('is_add', 0)
                    ->where('is_delete', 0)
                    ->where('lotteryType', $config['type'])
                    ->where('max_issue', $item['number']-1)
                    ->select(['issues', 'id'])->first();
//                dd($item, (array)$info);
                if (empty($info)) {
                    echo ($config['type'] == 1 ? '港彩' : '澳彩') . $item['keyword'] . "第" . $item['number'] . "期不存在\n";
                    continue;
                }
                $issues  = $info['issues'];
                if ($issues) {
                    $issues = json_decode($issues, true);
                    $issues = array_unique($issues);
                    if ($issues[0] == "第" . $item['number'] . "期") {
                        DB::table('year_pics')
                            ->where('id', $info['id'])
//                            ->where('year', $res['data']['year'])
//                            ->where('color', $item['color'])
//                            ->where('keyword', $item['keyword'])
//                            ->where('is_add', 0)
//                            ->where('is_delete', 0)
//                            ->where('lotteryType', $config['type'])
//                            ->where('max_issue', '<>', $item['number'])
                            ->update([
                                'max_issue'        => $item['number'],
                            ]);
                        echo ($config['type'] == 1 ? '港彩' : '澳彩') . $item['keyword'] . "第" . $item['number'] . "期已存在，跳过更新\n";
                    } else {
                        $firstIssue = ltrim($issues[0], '第');
                        $firstIssue = rtrim($firstIssue, '期');
                        if ($item['number'] == (int)$firstIssue + 1) { // 元旦前要修改
                            array_unshift($issues, "第" . $item['number'] . "期");
                            DB::table('year_pics')
                                ->where('id', $info['id'])
//                            ->where('year', $res['data']['year'])
//                            ->where('color', $item['color'])
//                            ->where('keyword', $item['keyword'])
//                            ->where('is_add', 0)
//                            ->where('is_delete', 0)
//                            ->where('lotteryType', $config['type'])
//                            ->where('max_issue', '<>', $item['number'])
                                ->update([
                                    'max_issue'        => $item['number'],
                                    'issues'            => json_encode($issues),
                                ]);
                            echo ($config['type'] == 1 ? '港彩' : '澳彩') . $item['keyword'] . "第" . $item['number'] . "期已存在，更新数据\n";
                        } else {
                            Log::error('命令【module:auto-xg-am-max-issue】'. ($config['type'] == 1 ? '港彩' : '澳彩') . ' 期数不连续 keyword: ' . $item['keyword'] );
                        }
                    }
                }
            }
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error('命令【module:auto-xg-am-max-issue】更新失败：' . $e->getMessage());
        }
        DB::commit();
    }

    /**
     * 批量更新数据库中的记录
     *
     * @param array $updateData 要更新的数据
     */
    public function batchUpdate($updateData)
    {
        // 这里可以根据实际情况，使用事务批量更新，或者使用 `upsert` 方法
        DB::beginTransaction();
        try {
            foreach ($updateData as $data) {
                DB::table('year_pics')
                    ->where('year', $data['year'])
                    ->where('color', $data['color'])
                    ->where('keyword', $data['keyword'])
                    ->where('is_add', 0)
                    ->where('is_delete', 0)
                    ->where('lotteryType', $data['lotteryType'])
                    ->where('pictureTypeId', $data['pictureTypeId'])
                    ->where('max_issue', '<>', $data['max_issue'])
                    ->update($data);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('批量更新失败：' . $e->getMessage());
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
