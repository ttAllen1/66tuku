<?php

namespace Modules\Admin\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XgAI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:xg-ai';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集香港 AI 信息';

    /**
     * 目标 API 基础 URL
     */
    private const BASE_URL = 'http://6hbd.me/api/MarkSix';

    /**
     * HTTP 请求头部信息
     */
    private $headers = [
        'Accept'           => 'application/json, text/plain, */*',
        'Accept-Encoding'  => 'gzip, deflate',
        'Accept-Language'  => 'zh-CN,zh;q=0.9',
        'Connection'       => 'keep-alive',
        'Host'             => '6hbd.me',
        'Origin'           => 'http://6hbd.me',
        'Referer'          => 'http://6hbd.me/xglhc/formula',
        'Sec-Fetch-Dest'   => 'empty',
        'Sec-Fetch-Mode'   => 'cors',
        'Sec-Fetch-Site'   => 'same-origin',
        'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3',
        'X-Requested-With' => 'XMLHttpRequest',
    ];

    /**
     * 执行命令
     */
    public function handle()
    {
        try {
            // 1. 获取 AI 公式列表
            $response = $this->fetchData("GetAiLawFormulaList?type=&childType=-1&periodCount=0&openOrder=-1&onlySpecial=0&lotId=20");

            if (!$response || $response['code'] !== 0) {
                Log::warning("获取 AI 公式列表失败: " . json_encode($response));
                return;
            }

            $lists = $response['body'] ?? [];
            if (empty($lists)) {
                Log::info("没有新的 AI 公式数据");
                return;
            }

            // 2. 检查是否已存在
            $latestPeriod = $lists[0]['Period'];
            $exists = DB::table('ai')->where('lotteryType', 1)->where('period', $latestPeriod)->exists();
            if ($exists) {
                Log::info("AI 数据已存在, 跳过处理: Period = {$latestPeriod}");
                return;
            }

            $data = [];
            $time = now()->toDateTimeString();

            // 3. 遍历列表，获取详细数据
            foreach ($lists as $value) {
                $detailResponse = $this->fetchData("GetAiLawFormulaInfo?id={$value['Id']}&lotId=20");

                $data[] = [
                    'lotteryType' => 1,
                    'childType'   => $value['ChildType'],
                    'period'      => $value['Period'],
                    'periodCount' => $value['PeriodCount'],
                    'thumbUrl'    => $value['ThumbUrl'],
                    'title'       => $value['Title'],
                    'content'     => $detailResponse['body']['Content'] ?? '',
                    'imageUrl'    => $detailResponse['body']['ImageUrl'] ?? '',
                    'type'        => $value['Type'],
                    'created_at'  => $time,
                ];
            }

            // 4. 批量插入数据
            $this->batchInsert('ai', $data);

            Log::info("成功采集 AI 数据, 期数: {$latestPeriod}");

        } catch (Exception $e) {
            Log::error("采集 AI 任务失败: " . $e->getMessage());
        }
    }

    /**
     * 发送 HTTP 请求获取数据
     *
     * @param string $endpoint
     * @return array|null
     */
    private function fetchData(string $endpoint): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)  // 限制超时时间，防止进程阻塞
                ->retry(3, 500) // 失败重试 3 次，每次间隔 500ms
                ->get(self::BASE_URL . "/$endpoint");

            return $response->json();
        } catch (Exception $e) {
            Log::error("HTTP 请求失败: {$endpoint} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * 批量插入数据
     *
     * @param string $table
     * @param array $data
     */
    private function batchInsert(string $table, array $data)
    {
        if (empty($data)) {
            return;
        }

        DB::table($table)->insert($data);
    }
}
