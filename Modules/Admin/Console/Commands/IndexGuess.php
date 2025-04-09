<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class IndexGuess extends Command
{
    private const LOTTERY_TYPES = [1, 2, 5, 6, 7]; // 使用常量定义
    private const CACHE_TTL = 3600; // 缓存时间（秒）
    private const MAX_RETRIES = 3; // 最大重试次数
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:index-guess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成首页预测';


    /**
     * 执行命令
     */
    public function handle()
    {
        while (true) {
            try {
                $this->processLotteries();
            } catch (\Throwable $e) {
                Log::error('首页预测任务异常: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
            }
            sleep(20);
        }
    }

    /**
     * 批量处理所有彩票类型（减少重复连接）
     */
    private function processLotteries(): void
    {
        $year = date('Y');
        $latestPeriods = $this->getLatestPeriods(); // 批量获取最新期数

        foreach (self::LOTTERY_TYPES as $lottery) {
            $this->processLottery($lottery, $year, $latestPeriods[$lottery] ?? null);
        }
    }

    /**
     * 处理单个彩票类型（封装独立逻辑）
     */
    private function processLottery(int $lottery, string $year, ?string $latestPeriod): void
    {
        try {
            $rdsData = $this->getRedisDataWithRetry($lottery);
            $nextIssue = $rdsData['next_issue'] ?? null;

            if (!$nextIssue || $latestPeriod === $nextIssue || in_array($rdsData['current_te_num'], ["后", "快", "步"])) {
                return;
            }

            $this->updateExistingRecords($lottery, $year, $nextIssue, $rdsData['current_te_num']);
            $this->insertNewGuess($lottery, $year, $nextIssue);

        } catch (\Throwable $e) {
            Log::error("彩票类型 {$lottery} 处理失败: " . $e->getMessage());
        }
    }

    function isChineseCharacter(string $char): bool
    {
        // 使用正则匹配 CJK Unified Ideographs 范围（中文汉字主要集中在这里）
        return preg_match('/^[\x{4e00}-\x{9fa5}]$/u', $char) === 1;
    }

    /**
     * 获取最新期数（批量查询优化）
     */
    private function getLatestPeriods(): array
    {
        return DB::table('index_guesses')
            ->whereIn('lotteryType', self::LOTTERY_TYPES)
            ->select('lotteryType', DB::raw('MAX(period) as latest_period'))
            ->groupBy('lotteryType')
            ->pluck('latest_period', 'lotteryType')
            ->toArray();
    }

    /**
     * 带重试机制的Redis数据获取
     */
    private function getRedisDataWithRetry(int $lottery): array
    {
        $retry = 0;
        while ($retry < self::MAX_RETRIES) {
            try {
                return $this->rdsData($lottery);
            } catch (\Throwable $e) {
                $retry++;
                if ($retry >= self::MAX_RETRIES) {
                    throw new \RuntimeException("Redis 数据获取失败: {$e->getMessage()}");
                }
                sleep(1);
            }
        }
        return [];
    }

    /**
     * 更新现有记录（批量更新优化）
     */
    private function updateExistingRecords(int $lottery, string $year, string $nextIssue, string $teNum): void
    {
        DB::table('index_guesses')
            ->where('lotteryType', $lottery)
            ->where('year', $year)
            ->where('period', '<', $nextIssue)
            ->update(['te_num' => $teNum]);
    }

    /**
     * 插入新预测数据（事务保护）
     */
    private function insertNewGuess(int $lottery, string $year, string $nextIssue): void
    {
        DB::transaction(function () use ($lottery, $year, $nextIssue) {
            $numData = $this->genNum();
            $recData = $this->genRec();

            DB::table('index_guesses')->insert([
                'lotteryType' => $lottery,
                'period'      => $nextIssue,
                'year'        => $year,
                'te_num'      => '00',
                'num_10'      => $numData['num_10'],
                'num_5'       => $numData['num_5'],
                'num_1'       => $numData['num_1'],
                'rec_9'       => $recData['rec_9'],
                'rec_6'       => $recData['rec_6'],
                'rec_3'       => $recData['rec_3'],
                'rec_1'       => $recData['rec_1'],
                'created_at'  => now()->toDateTimeString(),
            ]);
        });
    }

    private function rdsData($lottery): array
    {
        $rdsData = Redis::get('real_open_' . $lottery);  // 当拿到这数据时， 开奖已经完成
        $rdsData = explode(',', $rdsData);
        if ($lottery == 2) {
            $nextIssue = str_replace(date('Y'), '', $rdsData[8]);
        } else {
            $nextIssue = ltrim($rdsData[8], 0);
        }
        return [
            'next_issue' => $nextIssue,
            'current_te_num' => $rdsData[7],
        ];
    }

    private function genNum(): array
    {
        $numbers = range(01, 49);
        shuffle($numbers); // 打乱数组
        $randomNumbers = array_slice($numbers, 0, 10);
        $formattedNumbers = array_map(fn($num) => str_pad($num, 2, '0', STR_PAD_LEFT), $randomNumbers);
        $formattedNumbers5 = array_slice($formattedNumbers, 0, 5);
        $formattedNumbers1 = array_slice($formattedNumbers, 0, 1);

        return [
            'num_10'    => implode('.', $formattedNumbers),
            'num_5'     => implode('.', $formattedNumbers5),
            'num_1'     => implode('.', $formattedNumbers1),
        ];
    }

    private function genRec(): array
    {
        $arr = ["牛", "马", "羊", "鸡", "狗", "猪", "鼠", "虎", "兔", "龙", "蛇", "猴"];
        shuffle($arr);
        $rec9 = array_slice($arr, 0, 9);
        $rec6 = array_slice($rec9, 0, 6);
        $rec3 = array_slice($rec9, 0, 3);
        $rec1 = array_slice($rec9, 0, 1);
        return [
            'rec_9' => implode('', $rec9),
            'rec_6' => implode('', $rec6),
            'rec_3' => implode('', $rec3),
            'rec_1' => implode('', $rec1),
        ];
    }

}
