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
use Swoole\Coroutine\Http\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Swoole\Coroutine\run;

class RedPacketOpen extends Command
{
    private $_clientId = '';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:red-packet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每分钟计算是否有新的红包.';

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

        $res = DB::table('red_packets')
            ->whereIn('status', [-1, 1])
//            ->where('is_immediately', 0)
            ->select(['id', 'valid_date', 'status'])
            ->get()
            ->map(function($item) {
                return (array)$item;
            })
            ->toArray();
        if (!$res) {
            return ;
        }
        foreach($res as $k => $v) {
            $valid_date = json_decode($v['valid_date'], true);
            $start = $valid_date[0];
            $end = $valid_date[1];
            if ($v['status'] == -1) {
                if ( $start != 0 && $end != 0) {
                    if ( strtotime($start) <= time() && time()<strtotime($end) ) {
                        // 可开启
                        if (!$this->_clientId || Gateway::isOnline($this->_clientId)) {
                            $this->connectWS();
                        }
                        $this->sendRedPacket($v['id']);
                        DB::table('red_packets')->where('id', $v['id'])->update(['status'=>1]);
                    } elseif (time()>=strtotime($end)) {
                        DB::table('red_packets')->where('id', $v['id'])->update(['status'=>-2]);
                    }
                }
            } else if ($v['status'] == 1) {
                if ($end !=0 && time()>=strtotime($end)) {
                    DB::table('red_packets')->where('id', $v['id'])->update(['status'=>-2]);
                }
            }
        }
    }

    private function connectWS()
    {
        try{
            run(function () {
                $connected = false;
                while (!$connected) {
                    $client = new Client('127.0.0.1', 7272);
                    $ret = $client->upgrade('/');
                    if ($ret) {
                        $frameData = $client->recv();
                        if ($frameData === false) {
                            $connected = false;
                        } else {
                            $connected = true;
                            $recData = json_decode($frameData->data, true);
                            if ($recData['type'] == 'init') {
                                $this->_clientId = $recData['client_id'];
                            }
                        }

                    } else {
                        echo "Failed to connect. Retrying in 1 second...\n";
                        Coroutine::sleep(1); // 等待1秒后重试
                    }
                }
            });
        }catch (\Exception $exception) {
            dump($exception->getMessage());
        }
    }

    private function sendRedPacket($redId)
    {
        $room_id = 5;
        $from = 54377;
        // 发送到聊天室
        try{
            $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->findOrFail($from);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'当前绑定用户不存在']);
        }
        $sendData = [
            'room_id'   => $room_id,
            'message'   => ['status'=>1, 'redId'=>$redId, "is_receive"=>false],
            'style'     => 'red_envelope',
            'type'      => 'all',
            'to'        => [],
        ];

        $sendData['uuid'] = Str::uuid();

        $fromSession = [
            'user_id'           => $from,
            'user_name'         => $userData->nickname,
            'avatar'            => $userData->avatar,
            'room_id'           => $room_id,
        ];
        $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData);

        $room_check = Redis::get("chat_room_check_".$fromSession['room_id']);
        Gateway::sendToGroup($fromSession['room_id'], json_encode(['type'=>$sendMsg['type'], 'data'=>$sendMsg['data']]));

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
