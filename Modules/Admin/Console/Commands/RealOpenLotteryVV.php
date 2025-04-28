<?php

namespace Modules\Admin\Console\Commands;

use GatewayClient\Gateway;
use Illuminate\Console\Command;
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
use Swoole\Coroutine\Http\Client as CoHttpClient;

class RealOpenLotteryVV extends Command
{
    protected $_roomId = 5;

    protected $_configs = [
        0 => [
            'name'        => '香港六合彩',
            'lotteryType' => 1,
            'port'        => 777,
            'url'         => 'zhibo.chong0123.com',
            'url_77'      => 'api.49api66.com',
            'path_77'     => '/api/v1/index/test1',
            'port_77'     => 8443,
            'start'       => '21:00',
            'end'         => '23:00',
            'file_name'   => 'v_xg.json',
            'open_time'    => '16:30',  // 开奖开始时间
            'silent_before'=> 1800,      // 距离开奖多少秒前开始关注/推“准备开奖”
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
            'file_name'   => 'v_am_plus.json',
            'open_time'    => '21:30',
            'silent_before'=> 120,
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
            'file_name'   => 'v_tw.json',
            'open_time'    => '21:30',
            'silent_before'=> 120,
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
            'file_name'   => 'v_am.json',
            'open_time'    => '22:30',
            'silent_before'=> 120,
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
            'file_name'   => 'v_fckl8.json',
            'open_time'    => '21:30',
            'silent_before'=> 120,
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
            'file_name'   => 'v_oldam.json',
            'open_time'    => '21:30',
            'silent_before'=> 120,
        ],
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:real-open-vv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '实时开奖数据（重构版）';

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
        foreach ($this->_configs as $idx => $cfg) {
            // 跳过不需要的彩种
            if (in_array($idx, [1, 2, 3, 4, 5, 6])) continue;    // [2, 3]

            // 每个彩种启动一个进程来管理拉取与推送
            $proc = new Process(function() use ($cfg) {
                // 拉取协程
                go(function() use ($cfg) {
                    $this->pullLoop($cfg);
                });
                // 推送协程
                go(function() use ($cfg) {
                    $this->pushLoop($cfg);
                });
            });
            $proc->start();
        }
        // 等待所有子进程
        Process::wait(true);
    }

    /**
     * 判断当前是否处于准备开奖时间窗口
     *
     * @param string $openTime      开奖时间（格式：HH:MM）
     * @param int $silentBeforeSecs 提前多少秒进入准备阶段
     * @return bool
     */
    protected function isWithinWindow(string $openTime, int $silentBeforeSecs): bool
    {
        $now = time();
        $todayOpenTimestamp = strtotime(date('Y-m-d') . ' ' . $openTime);

        return $now >= ($todayOpenTimestamp - $silentBeforeSecs) && $now <= $todayOpenTimestamp;
    }

    /**
     * 拉取开奖接口数据
     *
     * @param array $cfg    彩种配置信息（包含url、端口、路径等）
     * @return array        成功返回开奖数据数组；失败返回空数组
     */
    protected function fetchData(array $cfg): array
    {
        try {
            // 解析 host
            $urlInfo = parse_url($cfg['url_77']);
            $host = $urlInfo['host'] ?? $cfg['url_77'];
            $port = $cfg['port_77'] ?? 443;


            // 实例化协程 HTTP 客户端
            $cli = new CoHttpClient($host, $port, true); // true=启用SSL，即HTTPS
//            dd($host, $port, $cli);
            // 设置请求超时、HTTP头等
            $cli->set([
                'timeout' => 3,
                'ssl_verify_peer' => false,   // 不验证SSL证书
                'ssl_allow_self_signed' => true, // 允许自签名证书
            ]);

            // 发起 GET 请求
            $cli->get($cfg['path_77']);

            // 检查 HTTP 状态码
            if ($cli->statusCode !== 200) {
                throw new \Exception("HTTP请求失败，状态码：{$cli->statusCode}");
            }

            // 获取响应体
            $body = $cli->body;


            // 关闭连接，释放资源
            $cli->close();

            // 尝试解析 JSON
            $json = json_decode($body, true);
            if (!is_array($json)) {
                throw new \Exception("接口返回内容非JSON格式：$body");
            }

            return $json;

        } catch (\Throwable $e) {
            // 记录异常日志
            Log::warning("拉取彩种{$cfg['lotteryType']}数据失败：" . $e->getMessage());
            return []; // 出错时返回空数组
        }
    }

    /**
     * 拉取最新开奖数据，增量入 Redis 队列，用于聊天室推送
     *
     * 流程控制：
     * - 每期开奖期间，只推送一次「准备开奖」提示
     * - 每开出一个号码，推送一次「number_open」事件
     * - 保证每一期流程清晰：准备阶段 -> 开奖进行中 -> 开奖完成
     */
    protected function pullLoop(array $cfg)
    {

        $queueKey    = "real_open_queue_{$cfg['lotteryType']}";
        $stageKey    = "real_open_stage_{$cfg['lotteryType']}";
        $issueKey    = "real_open_issue_{$cfg['lotteryType']}";
        $preparedKey = "real_open_prepared_{$cfg['lotteryType']}";
        $prevKey     = "real_open_prev_{$cfg['lotteryType']}";

        $today       = date('Ymd');
        $doneKey     = "real_open_done_{$cfg['lotteryType']}_{$today}";

        while (true) {
            try {
                $stage    = (int) Redis::get($stageKey);
                $prepared = (int) Redis::get($preparedKey);
                $done     = (int) Redis::get($doneKey);

                if ($done === 1) {
                    // 今日开奖已经完成，无需频繁拉取
                    Coroutine::sleep(60 * 60 * 20);
                    continue;
                }

                // —— 判断是否临近开奖窗口 ——
                $inWindow = $this->isWithinWindow($cfg['open_time'], $cfg['silent_before']);

                if ($inWindow || $stage > 0) {
                    // —— 拉取数据 ——
                    $dataObj = $this->fetchData($cfg);
                    if (!$dataObj) {
                        Coroutine::sleep(2);
                        continue;
                    }
                    $dataArr = $dataObj['Data'];
                    $currentIssue = "{$dataObj['Year']}-{$dataObj['Qi']}";

                    $lastIssue = Redis::get($issueKey);
                    if ($lastIssue !== $currentIssue) {
                        // 新一期切换，重置所有标记
                        Redis::set($issueKey, $currentIssue);
                        Redis::set($stageKey, 0);
                        Redis::set($preparedKey, 0);
                        Redis::del($prevKey);
                        $stage = 0;
                        $prepared = 0;
                    }

                    // —— 预热推送 prepare_open ——
                    if ($prepared === 0 && $inWindow) {
                        Redis::rpush($queueKey, json_encode(['event' => 'prepare_open']));
                        Redis::set($preparedKey, 1);
                    }

                    // —— 检测号码变化 number_open ——
                    foreach ($dataArr as $idx => $cell) {
                        $prevNumber = Redis::hget($prevKey, $idx);
                        $currNumber = $cell['number'];

                        if ($prevNumber !== $currNumber && ctype_digit($currNumber) && $currNumber != "00") {
                            Redis::rpush($queueKey, json_encode([
                                'event'     => 'number_open',
                                'index'     => $idx + 1,
                                'number'    => $currNumber,
                                'sx'        => $cell['sx'],
                                'style'     => $cell['style'],
                                'fullData'  => $dataObj,
                            ]));
                            $stage++;
                            Redis::set($stageKey, $stage);
                        }
                        Redis::hset($prevKey, $idx, $currNumber);
                    }

                    // —— 如果 stage 达到7，说明本期开奖完成 ——
                    if ($stage >= 8) {
                        Redis::set($doneKey, 1); // 标记今日开奖已完成
                        Redis::expire($doneKey, 86000); // 24小时过期（可选）
                    }

                    Coroutine::sleep(2);

                } else {
                    // 还未到 silent_before，低频检测
                    Coroutine::sleep(30);
                }

            } catch (\Throwable $e) {
                Log::error("pullLoop error for lotteryType {$cfg['lotteryType']}: ".$e->getMessage());
                Coroutine::sleep(10);
            }
        }
    }




    /**
     * 聊天室推送开奖进度
     * —— 负责从 redis 队列中取出开奖事件（准备开奖/开出号码）
     * —— 每次推送7个号码，未开出的号码同步接口提供的提示文本
     */
    protected function pushLoop(array $cfg)
    {
        $queueKey = "real_open_queue_{$cfg['lotteryType']}"; // 队列名称（每个彩种独立）
        $stageKey = "real_open_stage_{$cfg['lotteryType']}"; // 阶段计数器（防止乱推送）
        $prevKey = "real_open_prev_{$cfg['lotteryType']}";   // 上一次推送内容（防止重复推送）

        while (true) {
            try {
                // 阻塞式读取 redis 队列（超时时间设置为 0，永久等待）
                list(, $json) = Redis::blpop($queueKey, 0);
                $evt = json_decode($json, true);

                if (!$evt || empty($evt['event']) || empty($evt['fullData'])) {
                    // 非法数据，跳过
                    continue;
                }
                $fullObj = $evt['fullData'];
                $fullData = $evt['fullData']['Data'] ?? [];
                if (empty($fullData) || count($fullData) !== 7) {
                    // 数据不完整，通常是接口问题，跳过
                    continue;
                }

                // 组装聊天室需要推送的7个号码列表
                $list = [];
                foreach ($fullData as $item) {
                    $list[] = [
                        'number' => $item['number'],     // 当前号码（只要开出号码）
                        'sx'     => $item['sx'] ?? '',    // 生肖（用于前端显示）
                        'nim'    => $item['nim'] ?? '',   // 五行或颜色
                    ];
                }

                // 推送的基础消息体
                $message = [
                    'name'        => "【{$cfg['name']}】" . "{$evt['fullData']['Year']}-{$evt['fullData']['Qi']} 开奖号码:",
                    'lotteryType' => $cfg['lotteryType'],
                    'list'        => $list,
                ];

                // 判断事件类型
                if ($evt['event'] === 'prepare_open') {
                    // 🎯 准备开奖阶段：推送一次，标记 stage=0
                    Redis::set($stageKey, 0);
                    Redis::set($prevKey, md5(json_encode($list))); // 存上一次推送快照
                    $this->dispatch($cfg, $message, $fullObj);

                } elseif ($evt['event'] === 'number_open') {
                    // 🎯 正在开出号码阶段
                    $stage = (int) Redis::incr($stageKey); // 阶段递增
                    if ($stage > 7) {
                        // 超出阶段数（正常开完是7次），保护一下
                        Redis::del($stageKey);
                        Redis::del($prevKey);
                        continue;
                    }

                    $currentSnapshot = md5(json_encode($list));
                    $lastSnapshot = Redis::get($prevKey);

                    if ($currentSnapshot === $lastSnapshot) {
                        // 内容没有变化，防止重复推送
                        continue;
                    }

                    // 更新上一次推送快照
                    Redis::set($prevKey, $currentSnapshot);

                    $this->dispatch($cfg, $message, $fullObj);

                    // 如果开奖完成（第7次），清理阶段记录
                    if ($stage >= 7) {
                        Redis::del($stageKey);
                        Redis::del($prevKey);
                    }
                }

            } catch (\Throwable $e) {
                // 统一捕获异常，防止pushLoop线程崩溃
                Log::error("pushLoop error for lotteryType {$cfg['lotteryType']}: ".$e->getMessage());
                Coroutine::sleep(1); // 等待1秒后继续拉取
            }
        }
    }


    /**
     * 统一推送报码消息到聊天室
     *
     * @param array $cfg        彩种配置信息（包含 lotteryType、聊天室id等）
     * @param array $message    消息内容（包含 type、text 等字段）
     */
    protected function dispatch(array $cfg, array $message, $fullObj)
    {
        $fileName = $this->getFileName($cfg['lotteryType']);

        // 消息体，可以根据业务定制
        $writeData = [
            'data' => is_array($fullObj) ? json_encode($fullObj) : $fullObj,
            'lottery_type' => $cfg['lotteryType'],
            "code"        => 1,
            "msg"         => "success"
        ];

        // 这里实际推送，可以根据你的项目使用方式不同，做不同的处理
        // 比如使用 GatewayWorker 推送到 WebSocket 客户端
        try {

            // 1. 上传 S3
            $writeData = json_encode($writeData);
            try {
                (new BaseService())->upload2S3($writeData, 'open_lottery', $fileName);
            } catch (\Exception $e) {
                Log::error("PUSH S3 失败 彩种{$cfg['lotteryType']}: ".$e->getMessage());
            }

            // 2. 推送聊天室
            $this->sends($cfg['lotteryType'], $message);


//            Log::info("彩种{$cfg['lotteryType']} 推送成功：".$payload['text']);
        } catch (\Throwable $e) {
            Log::error("彩种{$cfg['lotteryType']} 推送失败：".$e->getMessage());
        }
    }



    /**
     * 根据彩种返回文件名
     */
    protected function getFileName(int $lotteryType): string
    {
        // 这里可根据映射关系返回 v_xg.json、v_am.json 等
        foreach ($this->_configs as $cfg) {
            if ($cfg['lotteryType'] === $lotteryType) {
                return $cfg['file_name'];
            }
        }
        return 'unknown.json';
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
                'user_name' => '66管理员',
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
                Log::channel('_push_err')->error('彩种' . ($i) . '推送成功');
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
