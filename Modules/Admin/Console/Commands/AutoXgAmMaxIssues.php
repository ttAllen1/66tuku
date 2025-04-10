<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AutoXgAmMaxIssues extends Command
{
    // 配置项移至常量提升可维护性
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 1000; // 毫秒
    private const BATCH_UPDATE_SIZE = 100;
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
//        [
//            'type'      => 2,
//            'color'     => 1,
//            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=1",
//        ],
//        [
//            'type'      => 2,
//            'color'     => 2,
//            'url'       => "https://49208.com/unite49/h5/index/search?year=2025&keyword=&color=2",
//        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:auto-xg-am-max-issue';       // 理论上一次性执行即可

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
        try {
            foreach ($this->_pic_configs as $config) {
                $this->processConfig($config);
            }
            $this->info('所有配置处理完成');
        } catch (\Exception $e) {
            Log::error('命令执行异常: '.$e->getMessage(), ['trace' => $e->getTrace()]);
            $this->error('处理失败: '.$e->getMessage());
        }
    }

    private function processConfig(array $config): void
    {
        $response = $this->fetchDataWithRetry($config['url'], $config['type']);
        $data = $this->validateResponse($response);

        $this->processItems(
            $data['data']['list'],
            $data['data']['year'],
            $config
        );
    }

    private function fetchDataWithRetry(string $url, int $type)
    {
        return retry(self::MAX_RETRIES, function() use ($url, $type) {
            $response = Http::withHeaders([
                'LotteryType' => $type,
                'User-Agent' => config('app.user_agent')
            ])
                ->withOptions(['verify' => false])
                ->timeout(10)
                ->get($url);

            if ($response->failed()) {
                throw new \Exception("HTTP请求失败: {$response->status()}");
            }
            return $response;
        }, self::RETRY_DELAY);
    }

    private function validateResponse($response): array
    {
        $data = $response->json();

        if (!Arr::has($data, ['code', 'data.list'])) {
            throw new \Exception('无效的响应结构');
        }

        if ($data['code'] !== 10000 || empty($data['data']['list'])) {
            throw new \Exception('业务逻辑错误: '.($data['message'] ?? '未知错误'));
        }

        return $data;
    }

    private function processItems(array $items, int $year, array $config): void
    {
        $existingIssue = $this->getExistingIssue($year, $config);
        $updates = [];

        foreach ($items as $item) {
            $updates[] = $this->prepareUpdateData($item, $existingIssue);

            // 批量更新
            if (count($updates) >= self::BATCH_UPDATE_SIZE) {
                $this->executeBatchUpdate($updates, $config);
                $updates = [];
            }
        }

        // 处理剩余数据
        if (!empty($updates)) {
            $this->executeBatchUpdate($updates, $config);
        }
    }

    private function getExistingIssue(int $year, array $config): ?array
    {
        $result = DB::table('year_pics')
            ->where('year', $year)
            ->where('color', $config['color'])
            ->where('is_add', 0)
            ->where('lotteryType', $config['type'])
            ->select('issues')
            ->first();

        return $result ? json_decode($result->issues, true) : null;
    }

    private function prepareUpdateData(array $item, ?array &$existingIssue): array
    {
        $update = [
            'max_issue' => $item['number'],
            'year' => $item['year'],
            'color' => $item['color'],
            'keyword' => $item['keyword'],
            'lotteryType' => $item['type'],
            'pictureTypeId' => $item['pictureTypeId']
        ];

        if ($existingIssue) {
            array_unshift($existingIssue, "第{$item['number']}期");
            $update['issues'] = json_encode($existingIssue);
        }

        return $update;
    }

    private function executeBatchUpdate(array $updates, array $config): void
    {
        try {
            DB::transaction(function () use ($updates, $config) {
                $caseSql = $this->buildCaseStatement($updates);

                DB::table('year_pics')
                    ->where('year', $config['year'])
                    ->where('color', $config['color'])
                    ->where('lotteryType', $config['type'])
                    ->where('is_add', 0)
                    ->update([
                        'max_issue' => DB::raw("CASE {$caseSql} ELSE max_issue END"),
                        'issues' => DB::raw("CASE WHEN keyword IN (".implode(',', array_fill(0, count($updates), '?')).") THEN ? ELSE issues END")
                    ], $this->prepareBindings($updates));
            });
        } catch (\Exception $e) {
            Log::error('批量更新失败: '.$e->getMessage(), ['updates' => $updates]);
            throw $e;
        }
    }

    private function buildCaseStatement(array $updates): string
    {
        $cases = [];
        foreach ($updates as $update) {
            $cases[] = "WHEN keyword = '{$update['keyword']}' THEN '{$update['max_issue']}'";
        }
        return implode(' ', $cases);
    }

    private function prepareBindings(array $updates): array
    {
        $bindings = [];
        foreach ($updates as $update) {
            $bindings[] = $update['keyword'];
            $bindings[] = $update['issues'];
        }
        return $bindings;
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
