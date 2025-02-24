<?php

namespace Modules\Admin\Console\Commands;

use GatewayClient\Gateway;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Api\Events\ChatEvent;
use Modules\Api\Models\User;
use Modules\Api\Services\room\RoomService;
use Modules\Common\Exceptions\CustomException;
use Swoole\Coroutine;
use Swoole\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class NewLotteryToChat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:lottery:2:chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将新开号码发送到聊天室.';

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
        for ($i = 1; $i <= 1; $i++) {
            $process = new Process(function () use ($i) {
                \Swoole\Coroutine\go(function () use ($i) {
                    while (true) {
                        try{
                            if (!Redis::get('lottery_real_open_over_' . $i . '_with_' . date('Y-m-d'))) {
//                                Coroutine::sleep(5);
//                                continue;
                            }
                            // 判断需不需要发送
                            if (!$res = $this->isNeedSend($i)) {
                                Coroutine::sleep(5);
                                continue;
                            }
                            // 发送
                            $this->sendNewLotteryToChat($res);
                            // 记录发送
                            DB::table('lottery_chats')->insert([
                                'lotteryType' => $i,
                                'year'        => date('Y'),
                                'issue'       => $res['issue'],
                                'lotteryTime' => $res['lotteryTime'],
                                'created_at'  => date('Y-m-d H:i:s')
                            ]);
                        }catch (\Exception $exception) {
                            Coroutine::sleep(5);
                            continue;
                        }
                    }
                });
            });
            $process->start();
        }
        Process::wait();
    }

    private function isNeedSend($lotteryType)
    {
        $real_open = Redis::get("real_open_" . $lotteryType);
        if (!$real_open) {
            return false;
        }
//        dd($real_open);
        $arr = explode(',', $real_open);
        if ($lotteryType == 2) {
            $arr[0] = str_replace($arr[0], '', date('Y'));
        }
        $res = DB::table('lottery_chats')
            ->where('lotteryType', $lotteryType)
            ->where('year', date('Y'))
            ->where('issue', $arr[0])
            ->value('id');
        if ($res) {
            return false;
        }
        $res = DB::table('history_numbers')
            ->where('lotteryType', $lotteryType)
            ->where('year', date('Y'))
            ->where('issue', $arr[0])
            ->select(['lotteryTime', 'issue', 'number_attr'])
            ->first();

        if (!$res) {
            return false;
        }
        return (array)$res;

    }

    private function sendNewLotteryToChat($res)
    {
//        $lotteryTime = $res['lotteryTime'];
//        $number_attr = json_decode($res['number_attr'], true);
//        dd($lotteryTime, $number_attr);
        $room_id = 10;
        $from = 54377;
        // 发送到聊天室
        try {
            $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->findOrFail($from);
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '当前绑定用户不存在']);
        }
        $sendData = [
            'room_id' => $room_id,
            'message' => json_decode($res['number_attr'], true),
            'style'   => 'lottery',
            'type'    => 'all',
            'to'      => [],
        ];

        $sendData['uuid'] = Str::uuid();

        $fromSession = [
            'user_id'   => $from,
            'user_name' => $userData->nickname,
            'avatar'    => $userData->avatar,
            'room_id'   => $room_id,
        ];
        $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData);

        $room_check = Redis::get("chat_room_check_" . $fromSession['room_id']);
        Gateway::sendToGroup($fromSession['room_id'], json_encode([
            'type' => $sendMsg['type'], 'data' => $sendMsg['data']
        ]));

        // 保存数据
        $sendMsg['from_user_id'] = $from;

        $sendMsg['status'] = $room_check == 0 ? 1 : 0;
        event(new ChatEvent($sendMsg));
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
