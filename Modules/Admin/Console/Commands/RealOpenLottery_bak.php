<?php

namespace Modules\Admin\Console\Commands;

use Carbon\Carbon;
use GatewayClient\Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Swoole\Coroutine;
use Swoole\Process;
use Symfony\Component\Console\Input\InputOption;
use Swoole\Coroutine\Http\Client;

class RealOpenLottery_bak extends Command
{
    protected $_url = [
        0   => [
            'lotteryType'   => 1,
            'port'          => 777,
            'url'           => 'zhibo.chong0123.com',
            'url_77'        => 'http://yc.kkjj.finance/data/xg.json',
            'start'         => '19:00',
            'end'           => '21:40'
        ],
        1   => [
            'lotteryType'   => 2,
            'port'          => 777,
            'url'           => 'zhibo.ahntsy.com',
            'url_77'        => 'http://yc.kkjj.finance/data/am.json',
            'start'         => '21:20',
            'end'           => '21:40'
        ],
        2   => [
            'lotteryType'   => 3,
            'port'          => 777,
            'url'           => 'zhibo2.ahntsy.com',
            'url_77'        => 'http://yc.kkjj.finance/data/tw.json',
            'start'         => '20:40',
            'end'           => '21:00'
        ],
        3   => [
            'lotteryType'   => 4,
            'port'          => 777,
            'url'           => 'zhibo4.ahntsy.com',
            'url_77'        => 'http://yc.kkjj.finance/data/xjp.json',
            'start'         => '18:30',
            'end'           => '18:50'
        ],
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:real-open1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '实时开奖数据.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
//        print_r(Redis::get('lottery_open_over_by_'.date('Y-m-d').'_with_1'));
//        $this->call('module:history-number');exit;
//        dd(Redis::get('lottery_start_open_one_number_1'));
//        Redis::set('lottery_real_open_date_1', '2023/07/12');
//        Redis::set('lottery_real_open_date_2', '2023/07/12');
//        Redis::set('lottery_real_open_date_3', '2023/07/14');
//        echo Redis::get('lottery_real_open_date_4');
//        dd(Redis::get('real_open_2'));
        for($i=0; $i<=3; $i++) {
            $process = new Process(function () use ($i) {
                go(function() use ($i) {
                    while(true) {
                        Redis::get('use_backup_source_with_'.date('Y-m-d'));
                        if ( $this->ifLotteryTime($i) && !Redis::get('lottery_open_over_by_'.date('Y-m-d').'_with_'.$this->_url[$i]['lotteryType'])) {
                            if (!Redis::get('use_backup_source_with_'.date('Y-m-d'))) {
                                // 使用四九
                                $client = new Client($this->_url[$i]['url'], $this->_url[$i]['port'], true);
                                $client->set([
                                    'timeout' => 0.001,
                                    'keep_alive'    => true
                                ]);
                                $client->get('/js/i1i1i1i1i1l1l1l1l0.js?_=eer4323');

                                if ($client->statusCode === -2) {
                                    // 超时
                                    Redis::set('use_backup_source_with_'.date('Y-m-d'), true);
                                    $client->close();
                                    $this->deal_77();
                                    continue;
                                }
                                // 处理响应
                                if ($client->statusCode !== 200) {
                                    Redis::set('use_backup_source_with_'.date('Y-m-d'), true);
                                    $client->close();
                                    $this->deal_77();
                                    continue;
                                }
                                $this->deal_49($i, $client);
                                $client->close();
                            }
                        }
                        Coroutine::sleep(3); // 休眠1秒
                    }
                });
            });
            // 启动进程
            $process->start();
        }

        // 等待所有进程结束
        Process::wait(true);
    }

    function isChineseCharacter($str) {
        return preg_match('/[\x{4e00}-\x{9fa5}]+/u', $str);
    }

    /**
     * 判断当前彩种是否在开奖时间段
     * @param $i
     * @return bool
     */
    private function ifLotteryTime($i): bool
    {
        $date = Redis::get('lottery_real_open_date_'.$this->_url[$i]['lotteryType']);

        $start = $this->_url[$i]['start'];
        $end = $this->_url[$i]['end'];
        $time = time();

        if ( ($time >= strtotime($date.' '.$start)) && ($time <= strtotime($date.' '.$end)) ) {
            return true;
        }

        return false;
    }

    private function deal_49($i, $client)
    {
        $jsContent = $client->body;
        $data = json_decode($jsContent, true)['k'];
        $original = Redis::get('real_open_'.$this->_url[$i]['lotteryType']);
        $original = explode(',', $original);
        $date = Carbon::parse(date('Y').'-'.$original[9].'-'.$original[10]);
        if ($date->isFuture()) {
            echo '今日彩种_'.$this->_url[$i]['lotteryType'].'_开奖已完毕 停止播报号码'.PHP_EOL;
            return ;
        }
        Redis::set('real_open_'.$this->_url[$i]['lotteryType'], $data);
        // 推送开奖
        Gateway::sendToGroup($this->_url[$i]['lotteryType'], json_encode(array(
            'type'      => 'real_open',
            'data'    => [
                'lotteryType'   => $this->_url[$i]['lotteryType'],
                'body'          => $data
            ]
        )));
        echo '正在推送 房间号 '.$this->_url[$i]['lotteryType'].' 数据'.PHP_EOL;
        // 写入json
//                                $writeData = [
//                                    "data"  => $data,
//                                    "code"  => 1,
//                                    "msg"   => "success"
//                                ];
//                                Storage::disk('public')->put('file.json', json_encode($writeData));


        $arr = explode(',', $data);
        if (!Redis::get('lottery_start_open_one_number_' . $this->_url[$i]['lotteryType']) && $this->isChineseCharacter($arr[1])) {
            Redis::set('lottery_start_open_one_number_'.$this->_url[$i]['lotteryType'], true);
        }
        if (!Redis::get('lottery_start_open_seven_number_' . $this->_url[$i]['lotteryType']) && !$this->isChineseCharacter($arr[7])) {
            Redis::set('lottery_start_open_seven_number_'.$this->_url[$i]['lotteryType'], true);
        }
//                                $this->doLast($this->_url[$i]['lotteryType'], $arr);
        // 本期最后一个号码开奖完毕
        if (Redis::get('lottery_start_open_one_number_' . $this->_url[$i]['lotteryType']) && Redis::get('lottery_start_open_seven_number_' . $this->_url[$i]['lotteryType'])) {
            // 确保每期开奖只执行一次此逻辑
            Log::channel('_real_open')->info('彩种_'.$this->_url[$i]['lotteryType'].'_最后一个号码开奖完成');

            Redis::set('lottery_start_open_one_number_'.$this->_url[$i]['lotteryType'], false);
            Redis::set('lottery_start_open_seven_number_'.$this->_url[$i]['lotteryType'], false);

            echo '房间号 '.$this->_url[$i]['lotteryType']. '推送完毕'.PHP_EOL;

//                                    $this->call('module:history-number');
        }
        // 本期完整开奖完毕
        $previousDate = Redis::get('lottery_real_open_date_'.$this->_url[$i]['lotteryType']);
        if (date('Y').'-'.$arr[9].'-'.$arr[10] != $previousDate) {
            Log::channel('_real_open')->info('彩种_'.$this->_url[$i]['lotteryType'].'_开奖完成');
            // 下一期时间更新
            Redis::set('lottery_real_open_date_'.$this->_url[$i]['lotteryType'], date('Y').'-'.$arr[9].'-'.$arr[10]);
            // 唯一标识当前彩种开奖完毕
            Redis::set('lottery_open_over_by_'.date('Y-m-d').'_with_'.$this->_url[$i]['lotteryType'], true);

//                                    $this->doLast($this->_url[$i]['lotteryType'], $arr, $previousDate);
        }
        $client->close();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
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
