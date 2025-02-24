<?php

namespace Modules\Admin\Console\Commands;

use GatewayClient\Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Api\Events\ChatEvent;
use Modules\Api\Models\User;
use Modules\Api\Models\UserChat;
use Modules\Api\Services\room\RoomService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class RedPacketAutoOpen extends Command
{
    private static $_RED_TYPE = 2;
        /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:red-packet-auto'; // 开始发送红包
        /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自定义时间区间内，自动发送并关闭红包.'; // 结束发送红包
    private $_isOpen = true;
    private $_start_time = "18";
    private $_end_time = "23";

    /**
     * Create a new command instance.
     *
     * @return void
     */

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        while (true) {

            // 第11 41分钟：将所有type=2的红包置为过期
            if (date("i") == 11 || date("i") == 41) {
                DB::table('red_packets')
                    ->where('type', self::$_RED_TYPE)
                    ->where('status', 1)
                    ->update(['status' => 0]);
            } else if (date("i") == 00 || date("i") == 30) {
                if (date("i") == 30 && date("H") == $this->_end_time) {
                    sleep(5);
                    continue;
                }
                if (!$this->_isOpen) {
                    sleep(5);
                    continue;
                }
                // 判断当前时间能否发送红包
                if (date("H") < $this->_start_time || date("H") > $this->_end_time) {
                    sleep(5);
                    continue;
                }
                if (Redis::get('red_package_type') != self::$_RED_TYPE) {
                    sleep(5);
                    continue;
                }
                if (!Redis::get('red_package_auto_switch')) {
                    sleep(5);
                    continue;
                }
                $this->_start_time = Redis::get('red_package_start');
                $this->_end_time = Redis::get('red_package_end');
                $name = Redis::get('red_package_names');
                if ($name) {
                    $name = explode(' ', $name);
                    $name = $name[array_rand($name)];
                } else {
                    $name = '大吉大利';
                }
                $res = DB::table('red_packets')
                    ->lockForUpdate()
                    ->whereRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') = ?", [date('Y-m-d H:i')])
                    ->value('id');
                if ($res) {
                    sleep(5);
                    continue;
                }
                $redId = DB::table('red_packets')
                    ->insertGetId([
                        'name'           => $name,
                        'total'          => 0,
                        'nums'           => 0,
                        'last_nums'      => 0,
                        'moneys'         => '[]',
                        'is_immediately' => 1,
                        'type'           => self::$_RED_TYPE,
                        'status'         => 1,
                        'start_date'     => date('Y-m-d H:i:s'),
                        'created_at'     => date('Y-m-d H:i')
                    ]);
                if ($redId) {
                    $this->sendRedPacket($redId);
                }
            }
        }

    }

    private function sendRedPacket($redId)
    {
        $room_id = 5;
        $from = 54377;
        // 发送到聊天室
        $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->find($from);
        $sendData = [
            'room_id' => $room_id,
            'message' => ['status' => 1, 'redId' => $redId, "is_receive" => false],
            'style'   => 'red_envelope',
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
        UserChat::query()
            ->insert([
                'from_user_id'      => $sendMsg['from_user_id'],
                'status'            => $sendMsg['status'],
                'from'              => json_encode($sendMsg['data']['from']),
                'to'                => json_encode($sendMsg['data']['to']),
                'room_id'           => $sendMsg['data']['room_id'],
                'style'             => $sendMsg['data']['style'],
                'type'              => $sendMsg['data']['type'],
                'message'           => is_array($sendMsg['data']['message']) ? json_encode($sendMsg['data']['message']) : strip_tags($sendMsg['data']['message']),
                'img_width'         => $sendMsg['data']['img_width'],
                'img_height'        => $sendMsg['data']['img_height'],
                'cate'              => $sendMsg['data']['cate'],
                'corpusTypeId'      => $sendMsg['data']['corpusTypeId'],
                'uuid'              => $sendMsg['data']['uuid'],
                'detail_id'         => $sendMsg['data']['detail_id'],
                'created_at'        => $sendMsg['data']['time'],
            ]);
//        event(new ChatEvent($sendMsg));
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
