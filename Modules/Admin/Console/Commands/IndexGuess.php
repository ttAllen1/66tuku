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

            if (!$nextIssue || $latestPeriod === $nextIssue) {
                return;
            }
//            if (!in_array($rdsData['current_te_num'], ["后", "快", "步", "宾", "00", "啦", "中"])) {
//                $this->updateExistingRecords($lottery, $year, $nextIssue, $rdsData['current_te_num']);
//                $this->insertNewGuess($lottery, $year, $nextIssue);
//            }
            if ( !$this->isChineseOrDoubleZero($rdsData['current_te_num'])) {
                $this->updateExistingRecords($lottery, $year, $nextIssue, $rdsData['current_te_num']);
                $this->insertNewGuess($lottery, $year, $nextIssue);
            }
        } catch (\Throwable $e) {
            Log::error("彩票类型 {$lottery} 处理失败: " . $e->getMessage());
        }
    }

    /**
     * 检测值是否为汉字或"00"
     * @param mixed $value 要检测的值
     * @return bool 如果是汉字或"00"返回true，否则false
     */
    public function isChineseOrDoubleZero($value): bool
    {
        // 1. 检查是否为"00"
        if ($value === "00") {
            return true;
        }

        // 2. 检查是否为单个汉字
        if (is_string($value) && mb_strlen($value) === 1) {
            // 正则匹配Unicode汉字范围（包括基本汉字和扩展汉字区）
            return (bool)preg_match('/^[\x{4e00}-\x{9fa5}\x{3400}-\x{4dbf}\x{20000}-\x{2a6df}\x{2a700}-\x{2b73f}\x{2b740}-\x{2b81f}\x{2b820}-\x{2ceaf}\x{2ceb0}-\x{2ebef}\x{30000}-\x{3134f}]$/u', $value);
        }

        return false;
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
            ->where('year', date('Y'))
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
        DB::transaction(function () use ($lottery, $year, $nextIssue, $teNum) {
            $record = DB::table('index_guesses')
                ->where('lotteryType', $lottery)
                ->where('year', $year)
                ->where('period', '<', $nextIssue)
                ->orderBy('period', 'desc') // 或 asc，取最接近的一期
                ->first();

            if ($record) {
                DB::table('index_guesses')
                    ->where('id', $record->id)
                    ->update(['te_num' => $teNum]);
            }
        });
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
