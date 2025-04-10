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
        [
            'type'      => 1,
            'color'     => 2,
            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=2",
        ],
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
        // 重试机制，避免请求失败
        $response = null;
        $retryCount = 3;
        while ($retryCount > 0) {
            $response = Http::withHeaders([
                'LotteryType' => $config['type'],
                'User-Agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
            ])->withOptions([
                'verify' => false
            ])->get($config['url']);

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
        if ($res['code'] != 10000 || empty($res['data']['list'])) {
            Log::error('命令【module:am-index-pic】数据格式错误或为空');
            return;
        }

        $list = $res['data']['list'];

        // 获取对应的year_pics的当前`issues`数据
        $issues = DB::table('year_pics')
            ->where('year', $res['data']['year'])
            ->where('color', $config['color'])
            ->where('is_add', 0)
            ->where('lotteryType', $config['type'])
            ->value('issues');

        if (!$issues) {
            Log::info('没有找到相关记录，跳过处理');
            return;
        }

        $issues = json_decode($issues, true);
        array_unshift($issues, "第" . $list[0]['number'] . "期");

        // 更新数据库时，使用批量更新，避免每次循环都操作数据库
        $updateData = [];
        foreach ($list as $v) {
            $updateData[] = [
                'year'             => $res['data']['year'],
                'color'            => $v['color'],
                'keyword'          => $v['keyword'],
                'lotteryType'      => $config['type'],
                'pictureTypeId'    => $v['pictureTypeId'],
                'max_issue'        => $v['number'],
                'issues'           => json_encode($issues),
            ];
        }

        // 批量更新操作
        $this->batchUpdate($updateData);
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
