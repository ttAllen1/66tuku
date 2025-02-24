<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenGamePeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:gen-game-periods';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成分分动物彩 和 三分动物彩.';
    private $_clientId = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle1()
    {
        try {
            // 获取当前日期
            $startDate = now();

            // 生成未来 365 天的数据
            for ($d = 1; $d < 366; $d++) {
                // 计算当前处理的日期
                $currentDate = $startDate->copy()->addDays($d)->format('Ymd') . '.js';

                $three = [];
                $pre = [];

                // 生成每天 1440 期数据
                for ($i = 1; $i <= 1440; $i++) {
                    $formatted_i = str_pad($i, 3, '0', STR_PAD_LEFT);

                    if ($i <= 480) {
                        $three[$formatted_i] = [
                            "one"   => $this->getGamePeriods(),
                            "two"   => $this->getGamePeriods(),
                            "three" => $this->getGamePeriods(),
                            "four"  => $this->getGamePeriods(true),
                            "five"  => $this->getGamePeriods(true),
                            "six"   => $this->getGamePeriods(true)
                        ];
                    }

                    $pre[$formatted_i] = [
                        "one"   => $this->getGamePeriods(),
                        "two"   => $this->getGamePeriods(),
                        "three" => $this->getGamePeriods(),
                        "four"  => $this->getGamePeriods(true),
                        "five"  => $this->getGamePeriods(true),
                        "six"   => $this->getGamePeriods(true)
                    ];
                }

                // 生成 JSON 格式的 JavaScript 文件
                $threeContent = 'const plan_three=' . json_encode($three, JSON_UNESCAPED_UNICODE) . ";";
                $preContent = 'const plan_one=' . json_encode($pre, JSON_UNESCAPED_UNICODE) . ";";

                // 上传到 S3
                (new BaseService())->upload2S3($threeContent, 'animal_three_period', $currentDate);
                (new BaseService())->upload2S3($preContent, 'animal_minute_period', $currentDate);
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            for ($i = 1; $i <= 1440; $i++) {
                $formatted_i = str_pad($i, 3, '0', STR_PAD_LEFT);
                if ($i <= 480) {
                    $three[$formatted_i] = [
                        "one"   => $this->getGamePeriods(),
                        "two"   => $this->getGamePeriods(),
                        "three" => $this->getGamePeriods(),
                        "four"  => $this->getGamePeriods(true),
                        "five"  => $this->getGamePeriods(true),
                        "six"   => $this->getGamePeriods(true)
                    ];
                }
                $pre[$formatted_i] = [
                    "one"   => $this->getGamePeriods(),
                    "two"   => $this->getGamePeriods(),
                    "three" => $this->getGamePeriods(),
                    "four"  => $this->getGamePeriods(true),
                    "five"  => $this->getGamePeriods(true),
                    "six"   => $this->getGamePeriods(true)
                ];
            }
            $date = now()->addDay()->format('Ymd') . '.js';
            (new BaseService())->upload2S3('const plan_three=' . json_encode($three, JSON_UNESCAPED_UNICODE) . ";", 'animal_three_period', $date);
            (new BaseService())->upload2S3('const plan_one=' . json_encode($pre, JSON_UNESCAPED_UNICODE) . ";", 'animal_minute_period', $date);

        } catch (\Exception $e) {

        }
    }

    private function getGamePeriods(bool $isFourFiveSix = false): array
    {
        if (!$isFourFiveSix) {
            // one、two、three 的规则
            $one = Arr::random(range(1, 6), 3); // 随机选 3 个数字
            $two = Arr::random(range(1, 6), 4); // 随机选 4 个数字
            $three = Arr::random(range(1, 6), 5); // 随机选 5 个数字

            shuffle($one); // 打乱 one 的顺序
            shuffle($two); // 打乱 two 的顺序
            shuffle($three); // 打乱 three 的顺序

            // 随机生成 "大/小" 和 "单/双"
            $size = Arr::random(['大', '小']);
            $parity = Arr::random(['单', '双']);

            return [
                [$one, $two, $three], // 嵌套数组
                $size,               // "大/小"
                $parity              // "单/双"
            ];
        }

        // four、five、six 的规则
        $base = Arr::random(range(1, 6), 5); // 随机选 5 个数字
        shuffle($base); // 打乱顺序

        $size = Arr::random(['大', '小']);
        $parity = Arr::random(['单', '双']);

        return [
            $base, // five 或 six 的数字数组
            $size, // "大/小"
            $parity // "单/双"
        ];
    }

    private function getGamePeriods1(): array
    {
//        shuffle
        $arr = [
            Arr::random(range(1, 6), 5),
            Arr::random(['大', '小']),
            Arr::random(['单', '双'])
        ];
        shuffle($arr[0]);
        return $arr;
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

//pgrep -f "artisan module:real-open" | xargs kill -9
//pgrep -f "artisan module:forecast-bets" | xargs kill -9
//
//nohup php artisan module:real-open > /dev/null 2>&1 &
//nohup php artisan module:forecast-bets  > /dev/null 2>&1 &
//nohup php artisan queue:work > /dev/null 2>&1 &
