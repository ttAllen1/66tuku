<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Modules\Common\Services\BaseService;

class MasterRanking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * 调度时直接用：php artisan module:master-ranking
     *
     * @var string
     */
    protected $signature = 'module:master-ranking';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '开奖后，更新高手榜：连对/连错/近5/10/20期/正确率等';

    /** @var int[] 支持的彩种类型 */
    protected $_lotteryTypes = [1, 2, 5, 6, 7];

    /** @var string[] 家禽列表，用于家野判断 */
    protected $_jiaqin = ["牛", "马", "羊", "鸡", "狗", "猪"];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = date('Y');
        foreach ($this->_lotteryTypes as $lotteryType) {
            try {
                $this->info("开始处理彩种 {$lotteryType} 的高手榜...");
                $this->processLotteryType($lotteryType, $year);
            } catch (\Throwable $e) {
                // 捕获异常并记录日志，继续下一个彩种
                Log::error("彩种 {$lotteryType} 更新高手榜出错：{$e->getMessage()}", [
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->error("彩种 {$lotteryType} 处理失败，已记录日志。" . $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * 处理单个彩种、单年度的高手榜更新
     *
     * @param int $lotteryType
     * @param int $year
     */
    protected function processLotteryType(int $lotteryType, int $year): void
    {
        // 1️⃣ 取最新一期开奖号码
        $draw = DB::table('history_numbers')
            ->where('year', $year)
            ->where('lotteryType', $lotteryType)
            ->orderByDesc('id')
            ->select(['issue', 'number', 'attr_sx', 'attr_wx', 'attr_bs', 'te_attr', 'total_attr'])
            ->first();

        if (empty($draw)) {
            $this->warn("彩种 {$lotteryType} 没有新的开奖信息，跳过。");
            return;
        }

        $issue = $draw->issue;
        $this->info("最新开奖期号：{$issue}");

        // 2️⃣ 处理所有 right_or_wrong = -1 的预测记录
        DB::table('master_rankings')
            ->where('year', $year)
            ->where('lotteryType', $lotteryType)
            ->where('issue', $issue)
            ->where('right_or_wrong', -1)
            ->chunkById(500, function ($chunk) use ($draw, $year, $lotteryType, $issue) {
                $this->processChunk($chunk, $draw);
            });

        $this->info("彩种 {$lotteryType} 期号 {$issue} 的更新完成。");
    }

    /**
     * 处理一批待更新的预测记录
     *
     * @param \Illuminate\Support\Collection $items  每条记录为 stdClass
     * @param object                         $draw   最新开奖信息
     */
    protected function processChunk($items, $draw): void
    {
        if ($items->isEmpty()) {
            return;
        }

        // 1️⃣ 解析开奖号码和属性到数组
        $numberArr   = explode(' ', $draw->number);
        $weiArr      = array_map(fn($v) => $v % 10, $numberArr);
        $attrSxArr   = explode(' ', $draw->attr_sx);
        $attrWxArr   = explode(' ', $draw->attr_wx);
        $attrBsArr   = explode(' ', $draw->attr_bs);
        $attrBsTxt   = array_map(fn($v) => $v == 1 ? '红波' : ($v == 2 ? '蓝波' : '绿波'), $attrBsArr);
        $teAttr      = json_decode($draw->te_attr, true);
        $teAttr['jiaye']       = in_array($teAttr['shengXiao'], $this->_jiaqin) ? '家禽' : '野兽';
        $teAttr['colorTxt']    = $teAttr['color'] == 1 ? '红波' : ($teAttr['color'] == 2 ? '蓝波' : '绿波');
        $sum                  = ($teAttr['number'] % 10) + intval($teAttr['number'] / 10);
        $teAttr['hedanshuang'] = $sum % 2 === 0 ? '合双' : '合单';
        $teAttr['head']        = intval($teAttr['number'] / 10);
        $teAttr['tail']        = $teAttr['number'] % 10;
        $teAttr['duan']        = intval(ceil($teAttr['number'] / 7));
        $teAttr['banbo']       = rtrim($teAttr['colorTxt'], '波') . $teAttr['oddEven'];
        $totalAttr            = json_decode($draw->total_attr, true);

        // 2️⃣ 遍历每条记录，计算更新值
        $updates = [];
        foreach ($items as $row) {
            $data = (array)$row; // 转成数组便于修改
            $configId = $data['config_id'];
            $contentArr = Str::contains($data['content'], '-')
                ? explode('-', $data['content'])
                : [$data['content']];

            // 判断本期是否命中
            $hit = $this->judgeHit($configId, $contentArr, $teAttr, $numberArr, $weiArr, $attrSxArr, $attrBsArr, $attrSxArr);

            // 更新连对/连错/最近数据
            $this->applyGenData($data, $hit);

            // 近5/10/20期统计
            $this->applyWinStats($data);

            // 计算最新准确率
            $data['accuracy'] = $data['total_count'] > 0
                ? intval($data['total_right'] / $data['total_count'] * 100)
                : 0;

            // 收集批量更新
            $updates[] = $data;
        }

        // 3️⃣ 批量更新：使用 CASE WHEN … END
//        dd($updates);
        $this->batchUpdateMasterRankings($updates);
    }

    /**
     * 判断这一条预测是否命中
     *
     * @param int   $configId
     * @param array $contentArr
     * @param array $teAttr
     * @param array $numberArr
     * @param array $weiArr
     * @param array $attrSxArr
     * @param array $attrBsArr
     * @return bool
     */
    protected function judgeHit(int $configId, array $contentArr, array $teAttr, array $numberArr, array $weiArr, array $attrSxArr, array $attrBsArr): bool
    {
        switch ($configId) {
            case 2: case 3: case 4:
            return in_array($teAttr['shengXiao'], $contentArr);
            case 6: case 7: case 8: case 9:
            return in_array($teAttr['number'], $contentArr);
            case 16:
            case 11:
                return empty(array_diff($contentArr, $attrSxArr));
            case 12:
                return in_array($contentArr[0], $weiArr);
            case 13: case 14:
            return empty(array_intersect($contentArr, $numberArr));
            case 15:
                return !empty(array_intersect($contentArr, $numberArr));
            case 17:
                return empty(array_diff($contentArr, $weiArr));
            case 19:
                return ($teAttr['oddEven'].'数') === $contentArr[0];
            case 20:
                return ($teAttr['bigSmall'].'数') === $contentArr[0];
            case 21:
                return $teAttr['jiaye'] === $contentArr[0];
            case 22:
                return in_array($teAttr['colorTxt'], $contentArr);
            case 23:
                return $teAttr['hedanshuang'] === $contentArr[0];
            case 25:
                return in_array($teAttr['head'], $contentArr);
            case 26: case 28:
            return in_array($teAttr['tail'], $contentArr);
            case 27:
                return in_array($teAttr['wuXing'], $contentArr);
            case 30:
                return $teAttr['shengXiao'] !== $contentArr[0];
            case 31:
                return !in_array($teAttr['number'], $contentArr);
            case 32:
                return $teAttr['head'] !== $contentArr[0];
            case 33:
                return $teAttr['tail'] !== $contentArr[0];
            case 34:
                return $teAttr['duan'] !== $contentArr[0];
            case 35:
                return $teAttr['wuXing'] !== $contentArr[0];
            case 36:
                return !in_array($teAttr['tail'], $contentArr);
            case 37:
                return !in_array($teAttr['shengXiao'], $contentArr);
            case 38:
                return $teAttr['banbo'] === $contentArr[0];
            default:
                return false;
        }
    }

    /**
     * 根据命中结果，更新连对/连错和最近20期数据
     *
     * @param array &$data
     * @param bool  $hit
     */
    protected function applyGenData(array &$data, bool $hit): void
    {

        if ($hit) {
            $data['right_or_wrong']   = 1;
            $data['total_right']      = ($data['total_right'] ?? 0) + 1;

            $data['current_right']    = ($data['current_right'] ?? 0) + 1;
            $data['current_wrong']    = 0;
            $data['recently_data']    = rtrim($data['recently_data'], '0') . '1';
            $data['max_right']        = max($data['max_right'] ?? 0, $data['current_right']);
        } else {
            $data['right_or_wrong']   = 0;
            $data['total_wrong']      = ($data['total_wrong'] ?? 0) + 1;
            $data['current_wrong']    = ($data['current_wrong'] ?? 0) + 1;
            $data['current_right']    = 0;
            $data['recently_data']    = rtrim($data['recently_data'], '0') . '-1';
            $data['max_wrong']        = max($data['max_wrong'] ?? 0, $data['current_wrong']);
        }
        $data['score']      = $data['total_count'] . '对' . $data['total_right'];
//        dd($data);
    }

    /**
     * 计算并更新近5/10/20期的战绩文本及准确率
     *
     * @param array &$data
     */
    protected function applyWinStats(array &$data): void
    {
        // 转成数组并去掉末尾空串
        $arr = array_filter(explode(', ', rtrim($data['recently_data'], ', ')), 'strlen');
//        dd($arr);
        foreach ([5, 10, 20] as $n) {
            if (count($arr) >= $n) {
                $slice = array_slice(array_reverse($arr), 0, $n);
                $right = count(array_filter($slice, fn($v) => intval($v) === 1));
//                $wrong = count(array_filter($slice, fn($v) => intval($v) === -1));
                $fn = $n==5?'five':($n==10?'ten':'twenty');
                $data["{$fn}_res"]        = "{$n}对{$right}";
                $data["{$fn}_accuracy"]   = intval($right / $n * 100);
            }
        }
    }


    /**
     * 批量更新 master_rankings 表
     *
     * @param array $updates 每项都是一条完整 $data 数组，包含 id 和所有要更新的字段
     */
    protected function batchUpdateMasterRankings(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        // 分批，防止单次 SQL 太长
        $fields = [
            'right_or_wrong', 'total_right', 'total_wrong',
            'current_right', 'current_wrong',
            'max_right', 'max_wrong',
            'recently_data', 'accuracy',
            'five_res', 'five_accuracy',
            'ten_res', 'ten_accuracy',
            'twenty_res', 'twenty_accuracy','score'
        ];

        foreach (array_chunk($updates, 200) as $chunk) {
            // 1. 收集本批次所有 id
            $ids = array_column($chunk, 'id');

            // 2. 构造每个字段的 CASE WHEN 片段
            $cases = [];
            foreach ($fields as $field) {
                $cases[$field] = [];
                foreach ($chunk as $row) {
                    $id = $row['id'];
                    $cases[$field][] = "WHEN {$id} THEN ?";
                }
            }

            // 3. 按字段优先顺序，flatten 绑定数组
            $bindings = [];
            foreach ($fields as $field) {
                foreach ($chunk as $row) {
                    $bindings[] = $row[$field] ?? null;
                }
            }

            // 4. 拼接 SET 部分
            $setSqlParts = [];
            foreach ($fields as $field) {
                $setSqlParts[] = "`{$field}` = CASE `id` "
                    . implode(' ', $cases[$field])
                    . " END";
            }
            $setSql = implode(",\n    ", $setSqlParts);

            // 5. 完整 UPDATE 语句
            $sql = "
            UPDATE `lot_master_rankings`
            SET
                {$setSql}
            WHERE `id` IN (" . implode(',', $ids) . ")
        ";

            // 6. 执行
            DB::update($sql, $bindings);
        }
    }
}
