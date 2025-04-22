<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\YearPic;
use Modules\Common\Services\BaseService;
use Swoole\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AutoPutDiscuss extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:auto-put-discuss';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动发布论坛';

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
        // 家禽野兽
        // 家禽	牛	马	羊	鸡	狗	猪
        // 野兽	鼠	虎	兔	龙	蛇	猴

        // 男肖	鼠	牛	虎	龙	马	猴	狗
        // 女肖	兔	蛇	羊	鸡	猪

        // 天肖	牛	兔	龙	马	猴	猪
        // 地肖	鼠	虎	蛇	羊	鸡	狗

        // 春肖	虎	兔	龙
        // 夏肖	蛇	马	羊
        // 秋肖	猴	鸡	狗
        // 冬肖	鼠	牛	猪

        // 琴肖	兔	蛇	鸡
        // 棋肖	鼠	牛	狗
        // 书肖	虎	龙	马
        // 画肖	羊	猴	猪

        // 文肖：鼠，猪，鸡，羊，龙，兔
        // 武肖：虎，牛，狗，猴，马，蛇

        // 阴性:	鼠、龙、蛇、马、狗、猪
        // 阳性:	牛、虎、兔、羊、猴、鸡

        $type = [
            'dan-shuang'     => [
                [
                    'title' => '单双中特',
                    'sub_title' => '单数',
                    'num'   => 3,
                    'color' => '#FF0000',
                    'refer' => [1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29, 31, 33, 35, 37, 39, 41, 43, 45, 47, 49],
                    'user_id'   => [
                        1, 2, 3
                    ]
                ],
                [
                    'title' => '单双中特',
                    'sub_title' => '双数',
                    'num'   => 4,
                    'color' => '#FF0000',
                    'refer' => [2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36, 38, 40, 42, 44, 46, 48],
                    'user_id'   => [
                        4, 5, 6
                    ]
                ]
            ],
            'da-xiao'        => [
                [
                    'title' => '大小中特',
                    'sub_title' => '特码大',
                    'num'   => 3,
                    'color' => '#FF0000',
                    'refer' => [25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49],
                    'user_id'   => [
                        7, 8, 9
                    ]
                ],
                [
                    'title' => '大小中特',
                    'sub_title' => '特码小',
                    'num'   => 4,
                    'color' => '#FF0000',
                    'refer' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24],
                    'user_id'   => [
                        10, 11, 12
                    ]
                ]
            ],
            'bo-se'          => [
                [
                    'title' => '波色中特',
                    'sub_title' => '一波中特',
                    'num'   => 1,
                    'color' => '#800080',
                    'refer' => ['红', '蓝', '绿'],
                    'user_id'   => [
                        13, 14, 15
                    ]
                ],
                [
                    'title' => '波色中特',
                    'sub_title' => '双波中特',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['红', '蓝', '绿'],
                    'user_id'   => [
                        16, 17, 18
                    ]
                ],
            ],
            'wen-wu'          => [
                [
                    'title' => '文武中特',
                    'sub_title' => '文官',
                    'num'   => 2,
                    'color' => '#800080', // 鼠，猪，鸡，羊，龙，兔
                    'refer' => ['鼠', '猪', '鸡', '羊', '龙', '兔'],
                    'user_id'   => [
                        19, 20, 21
                    ]
                ],
                [
                    'title' => '文武中特',
                    'sub_title' => '武官',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['虎', '牛', '狗', '猴', '马', '蛇'],
                    'user_id'   => [
                        22, 23, 24
                    ]
                ],
            ],
            'tian-di'       => [
                [
                    'title' => '天地肖',
                    'sub_title' => '天肖',
                    'num'   => 2,
                    'color' => '#FFA500',
                    'refer' => ['牛', '兔', '龙', '马', '猴', '猪'],
                    'user_id'   => [
                        25, 26, 27
                    ]
                ],
                [
                    'title' => '天地肖',
                    'sub_title' => '地肖',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['鼠', '虎', '蛇', '羊', '鸡', '狗'],
                    'user_id'   => [
                        28, 29, 30
                    ]
                ]
            ],
            'nan-nv'        => [
                [
                    'title' => '男女肖',
                    'sub_title' => '男肖',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['鼠', '牛', '虎', '龙', '马', '猴', '狗'],
                    'user_id'   => [
                        31, 32, 33
                    ]
                ],
                [
                    'title' => '男女肖',
                    'sub_title' => '女肖',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['兔', '蛇', '羊', '鸡', '猪'],
                    'user_id'   => [
                        34, 35, 36
                    ]
                ]
            ],
            'yin-yang'        => [
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阴肖',
                    'num'   => 3,
                    'color' => '#FFG500',
                    'refer' => ['鼠', '龙', '蛇', '马', '狗', '猪'],
                    'user_id'   => [
                        37, 38, 39
                    ]
                ],
                [
                    'title' => '阴阳肖',
                    'sub_title' => '阳肖',
                    'num'   => 2,
                    'color' => '#FFG500',
                    'refer' => ['牛', '虎', '兔', '羊', '猴', '鸡'],
                    'user_id'   => [
                        40, 41, 42
                    ]
                ]
            ],
            'qin-qi-shu-hua' => [
                [
                    'title' => '琴棋书画',
                    'sub_title' => '琴棋',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['兔', '蛇', '鸡', '鼠', '牛', '狗'],
                    'user_id'   => [
                        43, 44, 45
                    ]
                ],
                [
                    'title' => '琴棋书画',
                    'sub_title' => '书画',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['虎', '龙', '马', '羊', '猴', '猪'],
                    'user_id'   => [
                        46, 47, 48
                    ]
                ]
            ],
            'jia-qin-ye-shou' => [
                [
                    'title' => '家禽野兽',
                    'sub_title' => '家禽',
                    'num'   => 3,
                    'color' => '#FFA500',
                    'refer' => ['牛', '马', '羊', '鸡', '狗', '猪'],
                    'user_id'   => [
                        49, 50, 51
                    ]
                ],
                [
                    'title' => '家禽野兽',
                    'sub_title' => '野兽',
                    'num'   => 2,
                    'color' => '#0000FF',
                    'refer' => ['鼠', '虎', '兔', '龙', '蛇', '猴'],
                    'user_id'   => [
                        52, 53, 54
                    ]
                ]
            ],
            'chun-xia-qiu-dong'          => [
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '春',
                    'num'   => 2,
                    'color' => '#800080',
                    'refer' => ['虎', '兔', '龙'],
                    'user_id'   => [
                        55, 56, 57
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '夏',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['蛇', '马', '羊'],
                    'user_id'   => [
                        58, 59, 60
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '秋',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['猴', '鸡', '狗'],
                    'user_id'   => [
                        61, 62, 63
                    ]
                ],
                [
                    'title' => '春夏秋冬',
                    'sub_title' => '冬',
                    'num'   => 2,
                    'color' => '#808080',
                    'refer' => ['鼠', '牛', '猪'],
                    'user_id'   => [
                        64, 65, 66
                    ]
                ],
            ],
        ];

        $lotteryTypes = [1, 2, 5, 6, 7];
        $year = date('Y');
        $date = date('Y-m-d H:i:s');
        foreach ($lotteryTypes as $lotteryType) {
            // 计算当前期和上一期
            $nextIssue = (int)(new BaseService())->getNextIssue($lotteryType);
            $lastIssue = $nextIssue - 1;

            // 1. 从数据库读取上一期的 te_attr JSON
            $raw = DB::table('history_numbers')
                ->where('lotteryType', $lotteryType)
                ->where('year', $year)
                ->where('issue', str_pad($lastIssue, 3, '0', STR_PAD_LEFT))
                ->value('te_attr');
            // JSON 解码
            $actual = json_decode($raw, true) ?: [];

            // 2. 提取实际数据字段
            $actualNumber   = isset($actual['number'])   ? (int)$actual['number']   : null;
            $colorMap       = [1 => '红', 2 => '蓝', 3 => '绿'];
            $actualColor    = $colorMap[$actual['color']] ?? '';
            $actualZodiac   = $actual['shengXiao'] ?? '';
            $actualOddEven  = $actual['oddEven']   ?? '';
            $actualBigSmall = $actual['bigSmall']  ?? '';

            foreach ($type as $group) {
                foreach ($group as $v) {
                    foreach ($v['user_id'] as $userId) {
                        // 3. 生成下一期预测文本
                        $preds = collect($v['refer'])->random($v['num'])->all();
                        $newLine = "{$nextIssue}期：<span style=\"color:{$v['color']}\">{$v['sub_title']}</span>：" . implode(',', $preds);

                        // 4. 取出已有记录及内容行
                        $record = DB::table('discusses')
                            ->where(['lotteryType' => $lotteryType, 'user_id' => $userId, 'year'=>$year, 'title' => $v['title']])
                            ->first();
                        $oldContent = $record->content ?? '';
                        $lines = array_filter(explode(PHP_EOL, trim($oldContent)));

                        $evaluated = [];
                        // 5. 对上一期行追加“对/错”
                        foreach ($lines as $line) {
                            if (strpos($line, "{$lastIssue}期：") !== false) {
                                if (preg_match("/{$lastIssue}期：.*?：(.+)/u", $line, $m)) {
                                    $list = array_map('trim', explode(',', $m[1]));
                                    $ok = false;
                                    // 数字匹配
                                    if ($list && ctype_digit((string)$list[0])) {
                                        $ok = in_array($actualNumber, array_map('intval', $list), true);
                                    }
                                    // 波色匹配
                                    elseif (in_array($list[0], ['红','蓝','绿'], true)) {
                                        $ok = in_array($actualColor, $list, true);
                                    }
                                    // 单双匹配
                                    elseif (in_array($list[0], ['单','双'], true)) {
                                        $ok = ($actualOddEven === $list[0]);
                                    }
                                    // 大小匹配
                                    elseif (in_array($list[0], ['大','小'], true)) {
                                        $ok = ($actualBigSmall === $list[0]);
                                    }
                                    // 其余均当生肖匹配
                                    else {
                                        $ok = in_array($actualZodiac, $list, true);
                                    }
                                    $line .= $ok ? ' 对' : ' 错';
                                }
                            }
                            $evaluated[] = $line;
                        }

                        // 6. 拼接新内容，置顶新预测，保存历史
                        $updatedContent = $newLine . PHP_EOL . implode(PHP_EOL, $evaluated) . PHP_EOL;

                        // 7. 插入或更新数据库
                        if (! $record) {
                            DB::table('discusses')->insert([
                                'user_id'     => $userId,
                                'lotteryType' => $lotteryType,
                                'year'        => $year,
                                'title'       => $v['title'],
                                'content'     => $updatedContent,
                                'issue'       => $nextIssue,
                                'thumbUpCount'=> 100,
                                'views'       => 200,
                                'created_at'  => $date,
                                'updated_at'  => $date,
                            ]);
                        } elseif ($record->issue != $nextIssue) {
                            DB::table('discusses')
                                ->where('id', $record->id)
                                ->update([
                                    'content'    => $updatedContent,
                                    'issue'      => $nextIssue,
                                    'updated_at' => $date,
                                ]);
                        }
                    }
                }
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
