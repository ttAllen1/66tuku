<?php

namespace Modules\Admin\Console\Commands;

use GatewayClient\Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Api\Models\UserChat;
use Modules\Api\Services\room\RoomService;
use Modules\Common\Services\BaseService;
use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use Swoole\Process;
use Symfony\Component\Console\Input\InputOption;

class RealOpenLotteryV extends Command
{
    protected $_roomId = 5;

    protected $_url = [
        0 => [
            'name'        => '香港六合彩',
            'lotteryType' => 1,
            'port'        => 777,
            'url'         => 'zhibo.chong0123.com',
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_xg.json',
            'port_77'     => 80,
            'start'       => '21:00',
            'end'         => '23:00',
            'file_name'   => 'v_xg.json'
        ],
        1 => [
            'name'        => '新澳门六合彩',
            'lotteryType' => 2,  // 新澳门 49
            'port'        => 777,
            'url'         => 'zhibo3.118ghb.com',
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_am.json',
            'port_77'     => 80,
            'start'       => '21:00',
            'end'         => '23:30',
            'file_name'   => 'v_am_plus.json'
        ],
        2 => [
            'name'        => '台湾六合彩',
            'lotteryType' => 3,
            'port'        => 777,
            'url'         => 'zhibo2.2020kj.com',
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_tw.json',
            'port_77'     => 80,
            'start'       => '20:00',
            'end'         => '23:00',
            'file_name'   => 'v_tw.json'
        ],
        3 => [
            'name'        => '新加坡六合彩',
            'lotteryType' => 4,
            'port'        => 777,
            'url'         => 'zhibo4.2020kj.com',
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_xjp.json',
            'port_77'     => 80,
            'start'       => '18:30',
            'end'         => '23:50',
            'file_name'   => 'v_xjp.json'
        ],
        4 => [
            'name'        => '天天澳门六合彩',
            'lotteryType' => 5,
            'port'        => 0,
            'url'         => '',
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_48am.json',
            'port_77'     => 80,
            'start'       => '22:20',
            'end'         => '23:30',
            'file_name'   => 'v_am.json'
        ],
        5 => [
            'name'        => '快乐八六合彩',
            'lotteryType' => 6,
            'port'        => 0,
            'url'         => '', // http://yc.kkjj.finance/data/fckl8.json
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_fckl8.json',
            'port_77'     => 80,
            'start'       => '21:00',
            'end'         => '23:30',
            'file_name'   => 'v_fckl8.json'
        ],
        6 => [
            'name'        => '老澳六合彩',
            'lotteryType' => 7,
            'port'        => 0,
            'url'         => '', // http://yc.kkjj.finance/data/fckl8.json
            'url_77'      => 'yc.kkjj.finance',
            'path_77'     => '/data/v_oldam.json',
            'port_77'     => 80,
            'start'       => '21:00',
            'end'         => '23:30',
            'file_name'   => 'v_oldam.json'
        ],
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:real-open-v';

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
        return ;
        for ($i = 0; $i <= 6; $i++) {
            if ($i == 2 || $i == 3) {
                continue;
            }
            $process = new Process(function () use ($i) {
                go(function () use ($i) {
                    while (true) {
                        try {
                            if ($this->ifLotteryTime($i) && !Redis::get('lottery_real_open_over_manually_' . $this->_url[$i]['lotteryType'] . '_with_' . date('Y-m-d'))) { // && !Redis::get('lottery_open_over_by_'.date('Y-m-d').'_with_'.$this->_url[$i]['lotteryType'])
                                // 使用77开奖源
                                $client = new Client($this->_url[$i]['url_77'], $this->_url[$i]['port_77']);

                                $client->set([
                                    'timeout'    => 3,
                                    'keep_alive' => true
                                ]);
                                $client->get($this->_url[$i]['path_77']);

                                if ($client->statusCode === -2) {
                                    Coroutine::sleep(3);
                                    // 超时
//                                Redis::set('use_backup_source_with_', true);
                                    $client->close();
//                                $this->deal_49($i);
                                    continue;
                                }
                                // 处理响应
                                if ($client->statusCode != 200) {
                                    Coroutine::sleep(3);
//                                Redis::set('use_backup_source_with_', true);
                                    $client->close();
//                                $this->deal_49($i);

                                    continue;
                                }
                                $this->deal_77($i, $client);
                                $client->close();
                            }
                            Coroutine::sleep(3);
                        } catch (\Exception $exception) {
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



    /**
     * 判断当前彩种是否在开奖时间段
     * @param $i
     * @return bool
     */
    private function ifLotteryTime($i): bool
    {
//        if ($this->_url[$i]['lotteryType'] == 3) {
//            return true;
//        }
        $date = Redis::get('lottery_real_open_date_' . $this->_url[$i]['lotteryType']);

        $start = $this->_url[$i]['start'];
        $end = $this->_url[$i]['end'];
        $time = time();

        if (($time >= strtotime($date . ' ' . $start)) && ($time <= strtotime($date . ' ' . $end))) {
            return true;
        }

        return false;
    }

    private function deal_77($i, $client)
    {
        try {
            $jsContent = $client->body;
            $client->close();
            $data = json_decode($jsContent, true);
            // 判断开奖相关数据是否已变动

            if ($i == 0 && Redis::get('xg_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            } else if ($i == 1 && Redis::get('xin_ao_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            } else if ($i == 2 && Redis::get('tw_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            } else if ($i == 3 && Redis::get('xjp_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            } else if ($i == 4 && Redis::get('tian_ao_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            } else if ($i == 5 && Redis::get('kl8_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            } else if ($i == 6 && Redis::get('lao_ao_report') == 1) {
                $this->switchChat($i, $data, 3); // 2 关闭聊天室
            }

            // 写入json
            $writeData = [
                "data"        => $data,
                'lotteryType' => $this->_url[$i]['lotteryType'],
                "code"        => 1,
                "msg"         => "success"
            ];
            $writeData = json_encode($writeData);
            // 云存储
//            (new BaseService())->ALiOss($this->_url[$i]['file_name'], $writeData);
            (new BaseService())->upload2S3($writeData, 'open_lottery', $this->_url[$i]['file_name']);
            (new BaseService())->upload2S3($writeData, 'open_lottery', $this->_url[$i]['file_name'], 's4');
        } catch (\Exception $exception) {
            Log::channel('_real_open')->error('77开奖出错', ['message' => $exception->getLine() . '-' . $exception->getMessage()]);
        }

    }

    private function switchChat($i, $data, $switchNum)
    {
        try {
            $roomId = $this->_roomId;
            if (in_array($data['Data'][1]['number'], ['主', '马', '稍', '台', '新', '00', '正'])) {
                // 判断此时间段共有几个彩种同时开奖
                $current_count_lottery_sends = (int)Redis::get('current_count_lottery_sends_'.date('Y-m-d'));
                if ($current_count_lottery_sends <= 0 ) {
                    $totalJoinOpen = $this->totalJoinOpen();
                    if ($totalJoinOpen==0) {
                        return false;
                    }
                    Redis::setex('current_count_lottery_sends_'.date('Y-m-d'), 13*3600, $totalJoinOpen);
                }

//                Redis::setex("chat_room_status_" . $roomId, 2000, $switchNum); // 不用管

                // 开始禁言
                if (Redis::get("chat_room_status_" . $roomId . "_" . $i) == $switchNum) {
                    return true;
                }
                Redis::setex("chat_room_status_" . $roomId . "_" . $i, 1500, $switchNum);

                $issue = $i == 5 ? $data['Qi'] : $data['Nq'];
                $message = [
                    'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$issue. '准备开奖了',
                    'lotteryType' => $this->_url[$i]['lotteryType'],
                    'list'        => []
                ];
                $this->sends($i, $message);
//                Gateway::sendToGroup($roomId, json_encode(array(
//                    'type'    => 'room_stop',
//                    'room_id' => $roomId
//                )));
            } else if (is_numeric($data['Data'][1]['number']) && strlen($data['Data'][1]['number']) == 2 && $data['Data'][1]['number'] != 00 && Redis::get("chat_room_status_" . $roomId . '_' . $i) == 3) {
                if (!Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_1')) {
                    // 开始报码
                    $message = [
                        'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                        'lotteryType' => $this->_url[$i]['lotteryType'],
                        'list'        => [
                            [
                                'number' => $data['Data'][1]['number'],
                                'sx'     => $data['Data'][1]['sx'],
                                'nim'    => $data['Data'][1]['nim']
                            ]
                        ]

                    ];
                    $this->sends($i, $message);
                    Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_1', 13 * 3600, 1);
                }
                if (is_numeric($data['Data'][2]['number']) && $data['Data'][2]['number'] != 00) {
                    if (Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_1') && !Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_2')) {
                        // 开始报码
                        $message = [
                            'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                            'lotteryType' => $this->_url[$i]['lotteryType'],
                            'list'        => [
                                [
                                    'number' => $data['Data'][1]['number'],
                                    'sx'     => $data['Data'][1]['sx'],
                                    'nim'    => $data['Data'][1]['nim']
                                ],
                                [
                                    'number' => $data['Data'][2]['number'],
                                    'sx'     => $data['Data'][2]['sx'],
                                    'nim'    => $data['Data'][2]['nim']
                                ]
                            ]
                        ];
                        $this->sends($i, $message);
                        Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_2', 13 * 3600, 1);
                    }
                }
                if (is_numeric($data['Data'][3]['number']) && $data['Data'][3]['number'] != 00) {
                    if (Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_2') && !Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_3')) {
                        // 开始报码
                        $message = [
                            'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                            'lotteryType' => $this->_url[$i]['lotteryType'],
                            'list'        => [
                                [
                                    'number' => $data['Data'][1]['number'],
                                    'sx'     => $data['Data'][1]['sx'],
                                    'nim'    => $data['Data'][1]['nim']
                                ],
                                [
                                    'number' => $data['Data'][2]['number'],
                                    'sx'     => $data['Data'][2]['sx'],
                                    'nim'    => $data['Data'][2]['nim']
                                ],
                                [
                                    'number' => $data['Data'][3]['number'],
                                    'sx'     => $data['Data'][3]['sx'],
                                    'nim'    => $data['Data'][3]['nim']
                                ]
                            ]
                        ];
                        $this->sends($i, $message);
                        Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_3', 13 * 3600, 1);
                    }
                }
                if (is_numeric($data['Data'][4]['number']) && $data['Data'][4]['number'] != 00) {
                    if (Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_3') && !Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_4')) {
                        // 开始报码
                        $message = [
                            'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                            'lotteryType' => $this->_url[$i]['lotteryType'],
                            'list'        => [
                                [
                                    'number' => $data['Data'][1]['number'],
                                    'sx'     => $data['Data'][1]['sx'],
                                    'nim'    => $data['Data'][1]['nim']
                                ],
                                [
                                    'number' => $data['Data'][2]['number'],
                                    'sx'     => $data['Data'][2]['sx'],
                                    'nim'    => $data['Data'][2]['nim']
                                ],
                                [
                                    'number' => $data['Data'][3]['number'],
                                    'sx'     => $data['Data'][3]['sx'],
                                    'nim'    => $data['Data'][3]['nim']
                                ],
                                [
                                    'number' => $data['Data'][4]['number'],
                                    'sx'     => $data['Data'][4]['sx'],
                                    'nim'    => $data['Data'][4]['nim']
                                ]
                            ]
                        ];
                        $this->sends($i, $message);
                        Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_4', 13 * 3600, 1);
                    }
                }
                if (is_numeric($data['Data'][5]['number']) && $data['Data'][5]['number'] != 00) {
                    if (Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_4') && !Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_5')) {
                        // 开始报码
                        $message = [
                            'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                            'lotteryType' => $this->_url[$i]['lotteryType'],
                            'list'        => [
                                [
                                    'number' => $data['Data'][1]['number'],
                                    'sx'     => $data['Data'][1]['sx'],
                                    'nim'    => $data['Data'][1]['nim']
                                ],
                                [
                                    'number' => $data['Data'][2]['number'],
                                    'sx'     => $data['Data'][2]['sx'],
                                    'nim'    => $data['Data'][2]['nim']
                                ],
                                [
                                    'number' => $data['Data'][3]['number'],
                                    'sx'     => $data['Data'][3]['sx'],
                                    'nim'    => $data['Data'][3]['nim']
                                ],
                                [
                                    'number' => $data['Data'][4]['number'],
                                    'sx'     => $data['Data'][4]['sx'],
                                    'nim'    => $data['Data'][4]['nim']
                                ],
                                [
                                    'number' => $data['Data'][5]['number'],
                                    'sx'     => $data['Data'][5]['sx'],
                                    'nim'    => $data['Data'][5]['nim']
                                ]
                            ]
                        ];
                        $this->sends($i, $message);
                        Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_5', 13 * 3600, 1);
                    }
                }
                if (is_numeric($data['Data'][6]['number']) && $data['Data'][6]['number'] != 00) {
                    if (Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_5') && !Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_6')) {
                        // 开始报码
                        $message = [
                            'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                            'lotteryType' => $this->_url[$i]['lotteryType'],
                            'list'        => [
                                [
                                    'number' => $data['Data'][1]['number'],
                                    'sx'     => $data['Data'][1]['sx'],
                                    'nim'    => $data['Data'][1]['nim']
                                ],
                                [
                                    'number' => $data['Data'][2]['number'],
                                    'sx'     => $data['Data'][2]['sx'],
                                    'nim'    => $data['Data'][2]['nim']
                                ],
                                [
                                    'number' => $data['Data'][3]['number'],
                                    'sx'     => $data['Data'][3]['sx'],
                                    'nim'    => $data['Data'][3]['nim']
                                ],
                                [
                                    'number' => $data['Data'][4]['number'],
                                    'sx'     => $data['Data'][4]['sx'],
                                    'nim'    => $data['Data'][4]['nim']
                                ],
                                [
                                    'number' => $data['Data'][5]['number'],
                                    'sx'     => $data['Data'][5]['sx'],
                                    'nim'    => $data['Data'][5]['nim']
                                ],
                                [
                                    'number' => $data['Data'][6]['number'],
                                    'sx'     => $data['Data'][6]['sx'],
                                    'nim'    => $data['Data'][6]['nim']
                                ]
                            ]
                        ];
                        $this->sends($i, $message);
                        Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_6', 13 * 3600, 1);
                    }
                }
                if (is_numeric($data['Data'][7]['number']) && $data['Data'][7]['number'] != 00) {
                    if (Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_6') && !Redis::get('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_7')) {
                        // 开始报码
                        $message = [
                            'name'        => '【'.$this->_url[$i]['name'].'】第' .$data['Year'].'-'.$data['Qi']. '开奖号码：',
                            'lotteryType' => $this->_url[$i]['lotteryType'],
                            'list'        => [
                                [
                                    'number' => $data['Data'][1]['number'],
                                    'sx'     => $data['Data'][1]['sx'],
                                    'nim'    => $data['Data'][1]['nim']
                                ],
                                [
                                    'number' => $data['Data'][2]['number'],
                                    'sx'     => $data['Data'][2]['sx'],
                                    'nim'    => $data['Data'][2]['nim']
                                ],
                                [
                                    'number' => $data['Data'][3]['number'],
                                    'sx'     => $data['Data'][3]['sx'],
                                    'nim'    => $data['Data'][3]['nim']
                                ],
                                [
                                    'number' => $data['Data'][4]['number'],
                                    'sx'     => $data['Data'][4]['sx'],
                                    'nim'    => $data['Data'][4]['nim']
                                ],
                                [
                                    'number' => $data['Data'][5]['number'],
                                    'sx'     => $data['Data'][5]['sx'],
                                    'nim'    => $data['Data'][5]['nim']
                                ],
                                [
                                    'number' => $data['Data'][6]['number'],
                                    'sx'     => $data['Data'][6]['sx'],
                                    'nim'    => $data['Data'][6]['nim']
                                ],
                                [
                                    'number' => $data['Data'][7]['number'],
                                    'sx'     => $data['Data'][7]['sx'],
                                    'nim'    => $data['Data'][7]['nim']
                                ]
                            ]
                        ];
                        $this->sends($i, $message);
                        Redis::setex('sys_report_code_' . date('Y-m-d') . '_' . $this->_url[$i]['lotteryType'] . '_7', 13 * 3600, 1);
//                        Redis::setex("chat_room_status_" . $roomId . '_' . $i, 3600, 1);
                        Redis::decr('current_count_lottery_sends_'.date('Y-m-d'));
//                        if (Redis::decr('current_count_lottery_sends_'.date('Y-m-d')) <= 0) {
//                            Redis::set("chat_room_status_" . $roomId, 1);
//                            Gateway::sendToGroup($roomId, json_encode(array(
//                                'type'    => 'room_start',
//                                'room_id' => $roomId
//                            )));
//                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            Log::channel('_real_open')->error('77报码出错', ['message' => $exception->getLine() . '-' . $exception->getMessage()]);
            return true;
        }

    }

    /**
     * 当前时间点共有几个彩种在临近的时间段开奖
     * @return int
     */
    function totalJoinOpen()
    {
        return 1;
        try{
            $current = Carbon::now(); // 获取当前时间
            $hour = $current->hour;
            $minute = $current->minute;
            if ($hour == 22) {
                return 1;
            }

            $arr = [];
            if (Redis::get('xg_report') == 1) {
                $arr[] = 1;
            }
            if (Redis::get('xin_ao_report') == 1) {
                $arr[] = 2;
            }
            if (Redis::get('tw_report') == 1) {
                $arr[] = 3;
            }
            if (Redis::get('xjp_report') == 1) {
                $arr[] = 4;
            }
            if (Redis::get('tian_ao_report') == 1) {
                $arr[] = 5;
            }
            if (Redis::get('kl8_report') == 1) {
                $arr[] = 6;
            }
            if (Redis::get('lao_ao_report') == 1) {
                $arr[] = 7;
            }
            if (!$arr) {
                return 0;
            }
            $data = [];
            foreach ($arr as $k => $v) {
                $data[] = explode(',', Redis::get('real_open_' . $v));
            }
            foreach ($data as $k => $v) {
                $data[$k] = date('Y') . '-' . $v[9] . '-' . $v[10] . ' ' . str_replace(['点'], ':', rtrim($v[12], '分'));
            }

//            $current = Carbon::now()->setYear(2024)->setMonth(7)->setDay(14)->setHour(21)->setMinute(28); // 获取当前时间

            $startThreshold = $current->copy()->subMinutes(20); // 当前时间的10分钟前
            $endThreshold = $current->copy()->addMinutes(20); // 当前时间的10分钟后

            $filteredDates = array_filter($data, function ($date) use ($startThreshold, $endThreshold) {
                $date = Carbon::createFromFormat('Y-m-d H:i', $date);
                return $date >= $startThreshold && $date <= $endThreshold;
            });
            return count($filteredDates);
        }catch (\Exception $exception) {
            return 1;
        }
    }

    private function sends($i, $message, $style = 'sys_report_code')
    {
        try {
            $from = 54377;
            $roomId = $this->_roomId;
            // 发送到聊天室
//            $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->find($from);
            $sendData = [
                'room_id' => $roomId,
                'message' => $message,
                'style'   => $style,
                'type'    => 'all',
                'to'      => [],
            ];

            $sendData['uuid'] = Str::uuid();

            $fromSession = [
                'user_id'   => $from,
                'user_name' => '49管理员',
                'avatar'    => "/upload/images/20231119/pp3762zWBp25yOMvHlatvXFkgdVZR382TwklkYje.jpg",
                'room_id'   => $roomId,
            ];
            $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData);
            $res = Gateway::sendToGroup($fromSession['room_id'], json_encode([
                'type' => $sendMsg['type'], 'data' => $sendMsg['data']
            ]));
//            $res = Gateway::sendToClient('7f00000108fe000003b1', json_encode([
//                'type' => $sendMsg['type'], 'data' => $sendMsg['data']
//            ]));
            if ($res) {
                Log::channel('_push_err')->error('彩种' . ($i + 1) . '推送成功');
            }
            // 保存数据
            $sendMsg['from_user_id'] = $from;

            $sendMsg['status'] = 1;
            UserChat::query()
                ->insert([
                    'from_user_id' => $sendMsg['from_user_id'],
                    'status'       => $sendMsg['status'],
                    'from'         => json_encode($sendMsg['data']['from']),
                    'to'           => json_encode($sendMsg['data']['to']),
                    'room_id'      => $sendMsg['data']['room_id'],
                    'style'        => $sendMsg['data']['style'],
                    'type'         => $sendMsg['data']['type'],
                    'message'      => is_array($sendMsg['data']['message']) ? json_encode($sendMsg['data']['message']) : strip_tags($sendMsg['data']['message']),
                    'img_width'    => $sendMsg['data']['img_width'],
                    'img_height'   => $sendMsg['data']['img_height'],
                    'cate'         => $sendMsg['data']['cate'],
                    'corpusTypeId' => $sendMsg['data']['corpusTypeId'],
                    'uuid'         => $sendMsg['data']['uuid'],
                    'detail_id'    => $sendMsg['data']['detail_id'],
                    'created_at'   => $sendMsg['data']['time'],
                ]);
        } catch (\Exception $exception) {
            Log::channel('_push_err')->error('77报码推送出错', ['message' => $exception->getLine() . '-' . $exception->getMessage()]);
            return true;
        }
    }

    function isCurrentTimeBetween($start = "21:30", $end = "21:35")
    {
        // 获取当前时间
        $now = Carbon::now();

        // 创建开始时间和结束时间对象
        $startTime = Carbon::createFromTimeString($start);
        $endTime = Carbon::createFromTimeString($end);

        // 判断当前时间是否在开始时间和结束时间之间
        return $now->between($startTime, $endTime);
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

    private function deal_49($i): void
    {
        if (!$this->_url[$i]['url']) {
            return;
        }
        $client = new Client($this->_url[$i]['url'], $this->_url[$i]['port'], true);
        $client->set([
            'timeout'    => 3,
            'keep_alive' => true
        ]);
        $client->get('/js/i1i1i1i1i1l1l1l1l0.js');
        $jsContent = $client->body;

        $data = json_decode($jsContent, true)['k'];
        $data = $this->convert_str($this->_url[$i]['lotteryType'], $data);

        Redis::set('real_open_' . $this->_url[$i]['lotteryType'], $data);

        // 写入json
        $writeData = [
            "data"        => $data,
            'lotteryType' => $this->_url[$i]['lotteryType'],
            "code"        => 1,
            "msg"         => "success"
        ];
        $writeData = json_encode($writeData);
        Storage::disk('public_open')->put($this->_url[$i]['file_name'], $writeData);
        // 云存储
        (new BaseService())->ALiOss($this->_url[$i]['file_name'], $writeData);
//        echo '正在写入 房间号 '.$this->_url[$i]['lotteryType'].' 数据'.PHP_EOL;
        $client->close();

        $arr = explode(',', $data);

        // 上一期 ｜ 下一期期数更新
        if ($this->_url[$i]['lotteryType'] == 2) {
            Redis::set('lottery_last_issue_' . $this->_url[$i]['lotteryType'], str_replace(date('Y'), '', $arr[0]));
            Redis::set('lottery_real_open_issue_' . $this->_url[$i]['lotteryType'], str_replace(date('Y'), '', $arr[8]));
        } else {
            Redis::set('lottery_last_issue_' . $this->_url[$i]['lotteryType'], $arr[0]);
            Redis::set('lottery_real_open_issue_' . $this->_url[$i]['lotteryType'], $arr[8]);
        }

        if (!Redis::get('seven_code_trans_char_with_' . $this->_url[$i]['lotteryType'] . '_of_' . date('Y-m-d'))) {
            if ($this->isChineseCharacter($arr[7]) || $arr[7] == '00') {
                Redis::setex('seven_code_trans_char_with_' . $this->_url[$i]['lotteryType'] . '_of_' . date('Y-m-d'), 36000, true);
            }
            return;
        }
        if ($this->isChineseCharacter($arr[7]) || $arr[7] == '00') {
            // 第七个号码还没有开完
            return;
        }
        $this->extracted($i, $arr);
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

    function isChineseCharacter($str)
    {
        return preg_match('/[\x{4e00}-\x{9fa5}]+/u', $str);
    }

    /**
     * @param $i
     * @param $arr
     * @return void
     */
    private function extracted($i, $arr): void
    {
//        Timer::after(10000, function () use ($i, $arr) {
//            // 下一期时间更新
//            if (Redis::get('lottery_real_open_date_' . $this->_url[$i]['lotteryType']) == date('Y') . '-' . $arr[9] . '-' . $arr[10]) {
//                return;
//            }
//            if (date('m-d') == '12-31') {
//                Redis::set('lottery_real_open_date_' . $this->_url[$i]['lotteryType'], date('Y', strtotime('+1 year')) . '-' . $arr[9] . '-' . $arr[10]);
//            } else {
//                Redis::set('lottery_real_open_date_' . $this->_url[$i]['lotteryType'], date('Y') . '-' . $arr[9] . '-' . $arr[10]);
//            }
//            // 全局彩种唯一开奖完毕标识
//            Redis::setex('lottery_real_open_over_v_' . $this->_url[$i]['lotteryType'] . '_with_' . date('Y-m-d'), 13*3600, 1);
//        });
    }
}
