<?php

namespace Modules\Admin\Console\Commands;

use GatewayClient\Gateway;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Api\Models\UserChat;
use Modules\Api\Services\room\RoomService;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SmartChat extends Command
{
    protected $_issue;
    protected $_year;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:smart-chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '智能聊天';

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
        // 在时间区间内发送内容 图片
        //  110期：一肖
        //  图片
        try{
            $config = json_decode(Redis::get('chat_smart'), true);
            if ($config['chat_switch'] == 0) {
                return;
            }
            // 将时间字符串转换为 Carbon 对象
            $startChatTime = Carbon::createFromTimeString($config['chat_times'][0]);
            $endChatTime = Carbon::createFromTimeString($config['chat_times'][1]);
            $currentTime = Carbon::now();
            if (!$currentTime->between($startChatTime, $endChatTime)) {
                return ;
            }
            $this->_year = date('Y');
            $lotteryType = [1, 2, 3, 4, 6];
            foreach ($lotteryType as $v) {
                $this->_issue[$v] = (new BaseService())->getNextIssue($v);
            }

            // 编辑消息 文本或者图片
            if ($this->getRound() == 1) {
                $style = 'string';
                $content = $this->getContent();
            } else {
                $style = 'image';
                $content = $this->getPicture();
            }
            $this->sendRedPacket($style, $content);
        }catch (\Exception $exception) {
            dd($exception->getMessage());
        }
    }

    public function getRound(): int
    {
        return 1;
        $arr = [1, 2, 3];
        return $arr[array_rand($arr)];
    }

    public function getContent()
    {
        $round = $this->getRound();
        if ($round == 1) {
            // 生肖
            return $this->getSx();
        } else if ($round == 2) {
            // 数字
            return $this->getNumber();
        } else if ($round == 3) {
            // 家禽
            $content = '';
            if ($this->getRound() == 1) {
                $content .= $this->getRandomJQ();
            } else {
                $content .= $this->getRandomYS();
            }
            return $content;
        }
    }

    public function getRandomJQ()
    {
        $jq = (new BaseService())->jiaqin;
        $nums = array_rand($jq, rand(1, 6));
        $nums = is_array($nums) ? $nums : [$nums];
        $res = [];
        foreach($nums as $v) {
            $res[] = $jq[$v];
        }

        return implode(' ', $res);
    }

    public function getRandomYS()
    {
        $jq = (new BaseService())->yeshou;
        $nums = array_rand($jq, rand(1, 6));
        $nums = is_array($nums) ? $nums : [$nums];
        $res = [];
        foreach($nums as $v) {
            $res[] = $jq[$v];
        }
        return implode(' ', $res);
    }

    public function getSx()
    {
        $sx_12 = ["牛", "马", "羊", "鸡", "狗", "猪", "鼠", "虎", "兔", "龙", "蛇", "猴"];
        $round = $this->getRound();
        $randomElements = [];
        if ($round == 3) {
            $randomKeys = array_rand($sx_12, rand(1, 6));
            foreach ($randomKeys as $key) {
                $randomElements[] = $sx_12[$key];
            }
            return implode('', $randomElements);
        }
        $sx = ['一肖', '二肖', '三肖', '四肖', '五肖', '六肖', '七肖', '九肖'];
        $sxRound = array_rand($sx);
        $randomKeys = array_rand($sx_12, $sxRound + 1);
        $randomKeys = is_array($randomKeys) ? $randomKeys : [$randomKeys];
        foreach ($randomKeys as $key) {
            $randomElements[] = $sx_12[$key];
        }

        return $sx[$sxRound] . implode('', $randomElements);
    }

    public function getNumber()
    {
        $sx = [
            '01', '02', '07', '08', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46', '05',
            '06', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49', '03', '04', '09',
            '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'
        ];
        $randomElements = [];
        $round = $this->getRound();
        if ($round == 3 || $round == 1) {
            $randomKeys = array_rand($sx, rand(1, 6));
            foreach ($randomKeys as $key) {
                $randomElements[] = $sx[$key];
            }
            return implode('', $randomElements);
        }

        $randomKeys = array_rand($sx, 10);
        foreach ($randomKeys as $key) {
            $randomElements[] = $sx[$key];
        }

        return '十码' . implode(' ', $randomElements);
    }

    public function getPicture(): string
    {
//        $lotteryTypes = [2];
        $lotteryType = 2;
        $info = DB::table('year_pics')->where('lotteryType', $lotteryType)->where('year', $this->_year)->where('is_add', 0)->inRandomOrder()->limit(1)->first();
        $info = (array)$info;
        $picUrl = (new BaseService())->getPicUrl(1, $info['max_issue'], $info['keyword'], $info['lotteryType']);
        if ($this->checkImageExists($picUrl)) {
            return $picUrl;
        } else {
            $picUrl = (new BaseService())->getPicUrl(1, $info['max_issue'] - 1, $info['keyword'], $info['lotteryType']);
            if ($this->checkImageExists($picUrl)) {
                return $picUrl;
            } else {
                return $this->getPicture();
            }
        }

    }

    function checkImageExists($imageUrl): bool
    {
        // 使用 Laravel 的 Http 客户端发送 GET 请求获取远程文件内容
        $response = Http::get($imageUrl);

        // 检查请求是否成功
        return $response->successful();
    }

    private function sendRedPacket($style, $content)
    {
        $room_id = 5;
        $userData = DB::table('users')->where('is_chat', 1)->inRandomOrder()->limit(1)->select([
            'id', 'nickname', 'avatar'
        ])->first();
        $userData = (array)$userData;
        $from = $userData['id'];
        // 发送到聊天室
        $sendData = [
            'room_id' => $room_id,
            'message' => $content,
            'style'   => $style,
            'type'    => 'all',
            'to'      => [],
        ];

        $sendData['uuid'] = Str::uuid();

        $fromSession = [
            'user_id' => $from,
            'user_name' => $userData['nickname'],
            'avatar' => $userData['avatar'],
            'room_id' => $room_id,
        ];
        $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData, false);

        Gateway::sendToGroup($fromSession['room_id'], json_encode([
            'type' => $sendMsg['type'], 'data' => $sendMsg['data']
        ]));

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
