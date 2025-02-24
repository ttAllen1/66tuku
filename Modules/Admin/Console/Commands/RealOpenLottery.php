<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Modules\Common\Services\BaseService;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Timer;
use Symfony\Component\Console\Input\InputOption;
use Swoole\Coroutine\Http\Client;

class RealOpenLottery extends Command
{
    protected $_url = [
        0   => [
            'lotteryType'   => 1,
            'port'          => 777,
            'url'           => 'zhibo.chong0123.com',
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/xg.json',
            'port_77'       => 80,
            'start'         => '21:00',
            'end'           => '23:00',
            'file_name'     => 'xg.json'
        ],
        1   => [
            'lotteryType'   => 2,  // 新澳门 49
            'port'          => 777,
            'url'           => 'zhibo3.118ghb.com',
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/am.json',
            'port_77'       => 80,
            'start'         => '21:00',
            'end'           => '23:30',
            'file_name'     => 'am_plus.json'
        ],
        2   => [
            'lotteryType'   => 3,
            'port'          => 777,
            'url'           => 'zhibo2.2020kj.com',
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/tw.json',
            'port_77'       => 80,
            'start'         => '20:00',
            'end'           => '23:00',
            'file_name'     => 'tw.json'
        ],
        3   => [
            'lotteryType'   => 4,
            'port'          => 777,
            'url'           => 'zhibo4.2020kj.com',
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/xjp.json',
            'port_77'       => 80,
            'start'         => '18:30',
            'end'           => '23:50',
            'file_name'     => 'xjp.json'
        ],
        4   => [
            'lotteryType'   => 5,
            'port'          => 0,
            'url'           => '',
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/48am.json',
            'port_77'       => 80,
            'start'         => '21:20',
            'end'           => '23:30',
            'file_name'     => 'am.json'
        ],
        5   => [
            'lotteryType'   => 6,
            'port'          => 0,
            'url'           => '', // http://yc.kkjj.finance/data/fckl8.json
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/fckl8.json',
            'port_77'       => 80,
            'start'         => '21:00',
            'end'           => '23:30',
            'file_name'     => 'fckl8.json'
        ],
        6   => [
            'lotteryType'   => 7,
            'port'          => 0,
            'url'           => '', // http://yc.kkjj.finance/data/fckl8.json
            'url_77'        => 'yc.kkjj.finance',
            'path_77'       => '/data/oldam.json',
            'port_77'       => 80,
            'start'         => '21:00',
            'end'           => '23:30',
            'file_name'     => 'oldam.json'
        ],
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:real-open';

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
    }

    public function handle()
    {
        for($i=0; $i<=6; $i++) {
            $process = new Process(function () use ($i) {
                go(function() use ($i) {
                    while(true) {
                        try{
                            if ( $this->ifLotteryTime($i) && !Redis::get('lottery_real_open_over_manually_'.$this->_url[$i]['lotteryType'].'_with_'.date('Y-m-d'))) { // && !Redis::get('lottery_real_open_over_'.$this->_url[$i]['lotteryType'].'_with_'.date('Y-m-d'))
                                // 使用77开奖源
                                $client = new Client($this->_url[$i]['url_77'], $this->_url[$i]['port_77']);
                                $client->set([
                                    'timeout' => 3,
                                    'keep_alive'    => true
                                ]);
                                $client->get($this->_url[$i]['path_77']);

                                if ($client->statusCode === -2) {
                                    // 超时
//                                Redis::set('use_backup_source_with_', true);
                                    $client->close();
//                                $this->deal_49($i);
                                    continue;
                                }
                                // 处理响应
                                if ($client->statusCode != 200) {
//                                Redis::set('use_backup_source_with_', true);
                                    $client->close();
//                                $this->deal_49($i);

                                    continue;
                                }
                                $this->deal_77($i, $client);
                                $client->close();
                            }
                            Coroutine::sleep(4);
                        }catch (\Exception $exception) {
                            continue;
                        }
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
        return true;
        if ($this->_url[$i]['lotteryType'] == 6) {
            return true;
        }
        $date = Redis::get('lottery_real_open_date_'.$this->_url[$i]['lotteryType']);

        $start = $this->_url[$i]['start'];
        $end = $this->_url[$i]['end'];
        $time = time();

        if ( ($time >= strtotime($date.' '.$start)) && ($time <= strtotime($date.' '.$end)) ) {
            return true;
        }

        return false;
    }

    private function deal_77($i, $client)
    {
        try{
            $jsContent = $client->body;
            $client->close();
            $data = json_decode($jsContent, true)['data'];
            if ($i == 1) {
                $dataArr = explode(',', $data);
                $dataArr[0] = date('Y').$dataArr[0];
                $dataArr[8] = date('Y').$dataArr[8];
                $data = implode(',', $dataArr);
            }
//            if (Redis::get('lottery_real_open_over_'.$this->_url[$i]['lotteryType'].'_with_'.date('Y-m-d'))) {
//                // 开奖完成后
//
//            }
            Redis::set('real_open_'.$this->_url[$i]['lotteryType'], $data);
            // 写入json
            $writeData = [
                "data"          => $data,
                'lotteryType'   => $this->_url[$i]['lotteryType'],
                "code"          => 1,
                "msg"           => "success"
            ];
            $writeData = json_encode($writeData);
            Storage::disk('public_open')->put($this->_url[$i]['file_name'], $writeData);
            // 云存储
//            (new BaseService())->ALiOss($this->_url[$i]['file_name'], $writeData);
            (new BaseService())->upload2S3($writeData, 'open_lottery', $this->_url[$i]['file_name']);

            $arr = explode(',', $data);

            // 上一期 ｜ 下一期期数更新
            if( $this->_url[$i]['lotteryType']==2 ) {
                Redis::set('lottery_last_issue_'.$this->_url[$i]['lotteryType'], str_replace(date('Y'), '', $arr[0]));
                Redis::set('lottery_real_open_issue_'.$this->_url[$i]['lotteryType'], str_replace(date('Y'), '', $arr[8]));
            } else {
                Redis::set('lottery_last_issue_'.$this->_url[$i]['lotteryType'], $arr[0]);
                Redis::set('lottery_real_open_issue_'.$this->_url[$i]['lotteryType'], $arr[8]);
            }

            if (!Redis::get('seven_code_trans_char_with_'.$this->_url[$i]['lotteryType'].'_of_'.date('Y-m-d'))) {
                if ($this->isChineseCharacter($arr[7]) || $arr[7] == '00') {
                    Redis::setex('seven_code_trans_char_with_'.$this->_url[$i]['lotteryType'].'_of_'.date('Y-m-d'), 36000, true);
                }
                return;
            }
            if ($this->isChineseCharacter($arr[7]) || $arr[7] == '00') {
                // 第七个号码还没有开完
                return;
            }
            $this->extracted($i, $arr);
        }catch (\Exception $exception) {
            Log::channel('_real_open')->error('77开奖出错', ['message'=>$exception->getMessage()]);
        }

    }

    private function deal_49($i): void
    {
        if (!$this->_url[$i]['url']) {
            return;
        }
        $client = new Client($this->_url[$i]['url'], $this->_url[$i]['port'], true);
        $client->set([
            'timeout'       => 3,
            'keep_alive'    => true
        ]);
        $client->get('/js/i1i1i1i1i1l1l1l1l0.js');
        $jsContent = $client->body;

        $data = json_decode($jsContent, true)['k'];
        $data = $this->convert_str($this->_url[$i]['lotteryType'], $data);

        Redis::set('real_open_'.$this->_url[$i]['lotteryType'], $data);

        // 写入json
        $writeData = [
            "data"          => $data,
            'lotteryType'   => $this->_url[$i]['lotteryType'],
            "code"          => 1,
            "msg"           => "success"
        ];
        $writeData = json_encode($writeData);
        Storage::disk('public_open')->put($this->_url[$i]['file_name'], $writeData);
        // 云存储
        (new BaseService())->ALiOss($this->_url[$i]['file_name'], $writeData);
//        echo '正在写入 房间号 '.$this->_url[$i]['lotteryType'].' 数据'.PHP_EOL;
        $client->close();

        $arr = explode(',', $data);

        // 上一期 ｜ 下一期期数更新
        if( $this->_url[$i]['lotteryType']==2 ) {
            Redis::set('lottery_last_issue_'.$this->_url[$i]['lotteryType'], str_replace(date('Y'), '', $arr[0]));
            Redis::set('lottery_real_open_issue_'.$this->_url[$i]['lotteryType'], str_replace(date('Y'), '', $arr[8]));
        } else {
            Redis::set('lottery_last_issue_'.$this->_url[$i]['lotteryType'], $arr[0]);
            Redis::set('lottery_real_open_issue_'.$this->_url[$i]['lotteryType'], $arr[8]);
        }

        if (!Redis::get('seven_code_trans_char_with_'.$this->_url[$i]['lotteryType'].'_of_'.date('Y-m-d'))) {
            if ($this->isChineseCharacter($arr[7]) || $arr[7] == '00') {
                Redis::setex('seven_code_trans_char_with_'.$this->_url[$i]['lotteryType'].'_of_'.date('Y-m-d'), 36000, true);
            }
            return;
        }
        if ($this->isChineseCharacter($arr[7]) || $arr[7] == '00') {
            // 第七个号码还没有开完
            return;
        }
        $this->extracted($i, $arr);
    }

    /**
     * @param $i
     * @param $arr
     * @return void
     */
    private function extracted($i, $arr): void
    {
        Timer::after(20000, function () use ($i, $arr) {
            // 下一期时间更新
            if (Redis::get('lottery_real_open_date_' . $this->_url[$i]['lotteryType']) == date('Y') . '-' . $arr[9] . '-' . $arr[10]) {
                return;
            }
            if (date('m-d') == '12-31') {
                Redis::set('lottery_real_open_date_' . $this->_url[$i]['lotteryType'], date('Y', strtotime('+1 year')) . '-' . $arr[9] . '-' . $arr[10]);
            } else {
                Redis::set('lottery_real_open_date_' . $this->_url[$i]['lotteryType'], date('Y') . '-' . $arr[9] . '-' . $arr[10]);
            }
            // 全局彩种唯一开奖完毕标识
            Redis::setex('lottery_real_open_over_' . $this->_url[$i]['lotteryType'] . '_with_' . date('Y-m-d'), 13*3600, 1);
        });
    }

    private function convert_str($lotteryType, $data)
    {
        $data = explode(',', $data);
        if ($lotteryType == 1) {
            if ($this->isChineseCharacter($data[1])) {
                $data[1] = '特';
                $data[2] = '区';
                $data[3] = '总';
                $data[4] = '站';
                $data[5] = '开';
                $data[6] = '奖';
                $data[7] = '快';
            } else if ($this->isChineseCharacter($data[2])) {
                $data[2] = '区';
                $data[3] = '总';
                $data[4] = '站';
                $data[5] = '开';
                $data[6] = '奖';
                $data[7] = '快';
            } else if ($this->isChineseCharacter($data[3])) {
                $data[3] = '总';
                $data[4] = '站';
                $data[5] = '开';
                $data[6] = '奖';
                $data[7] = '快';
            } else if ($this->isChineseCharacter($data[4])) {
                $data[4] = '站';
                $data[5] = '开';
                $data[6] = '奖';
                $data[7] = '快';
            } else if ($this->isChineseCharacter($data[5])) {
                $data[5] = '开';
                $data[6] = '奖';
                $data[7] = '快';
            } else if ($this->isChineseCharacter($data[6])) {
                $data[6] = '奖';
                $data[7] = '快';
            } else if ($this->isChineseCharacter($data[7])) {
                $data[7] = '快';
            }
        } elseif ($lotteryType == 2) {
            if ($this->isChineseCharacter($data[1])) {
                $data[1] = '与';
                $data[2] = '澳';
                $data[3] = '门';
                $data[4] = '台';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[2])) {
                $data[2] = '澳';
                $data[3] = '门';
                $data[4] = '台';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[3])) {
                $data[3] = '门';
                $data[4] = '台';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[4])) {
                $data[4] = '台';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[5])) {
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[6])) {
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[7])) {
                $data[7] = '中';
            }
        } elseif ($lotteryType == 3) {
            if ($this->isChineseCharacter($data[1])) {
                $data[1] = '台';
                $data[2] = '湾';
                $data[3] = '开';
                $data[4] = '奖';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[2])) {
                $data[2] = '湾';
                $data[3] = '开';
                $data[4] = '奖';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[3])) {
                $data[3] = '开';
                $data[4] = '奖';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[4])) {
                $data[4] = '奖';
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[5])) {
                $data[5] = '同';
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[6])) {
                $data[6] = '步';
                $data[7] = '中';
            } else if ($this->isChineseCharacter($data[7])) {
                $data[7] = '中';
            }
        } elseif ($lotteryType == 4) {
            if ($this->isChineseCharacter($data[1])) {
                $data[1] = '新';
                $data[2] = '加';
                $data[3] = '坡';
                $data[4] = '同';
                $data[5] = '步';
                $data[6] = '开';
                $data[7] = '奖';
            } else if ($this->isChineseCharacter($data[2])) {
                $data[2] = '加';
                $data[3] = '坡';
                $data[4] = '同';
                $data[5] = '步';
                $data[6] = '开';
                $data[7] = '奖';
            } else if ($this->isChineseCharacter($data[3])) {
                $data[3] = '坡';
                $data[4] = '同';
                $data[5] = '步';
                $data[6] = '开';
                $data[7] = '奖';
            } else if ($this->isChineseCharacter($data[4])) {
                $data[4] = '同';
                $data[5] = '步';
                $data[6] = '开';
                $data[7] = '奖';
            } else if ($this->isChineseCharacter($data[5])) {
                $data[5] = '步';
                $data[6] = '开';
                $data[7] = '奖';
            } else if ($this->isChineseCharacter($data[6])) {
                $data[6] = '开';
                $data[7] = '奖';
            } else if ($this->isChineseCharacter($data[7])) {
                $data[7] = '奖';
            }
        }

        return implode(',', $data);
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
