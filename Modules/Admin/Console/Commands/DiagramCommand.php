<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Api\Models\AuthConfig;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Swoole\Coroutine;
use Swoole\Coroutine\WaitGroup;
use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;
use Swoole\Process;

class DiagramCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:diagram {--d|diagram : 图解采集}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '图解：采集更新.';

    /**
     * @name  主机HOST
     * @var string
     */
    static $host = 'http://18.166.68.127'; //主机

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
        if ($this->option('diagram'))
        {
            $this->diagram();
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

    /**
     * 请求封装
     * @param $url
     * @param $params
     * @param string $method
     * @return mixed
     */
    private function request($url, $params, $method = 'GET')
    {
        try {
            $query = http_build_query($params);
            $options['http'] = [
                'timeout' => 6,
                'method' => $method,
                'header' => "Content-type: application/x-www-form-urlencoded\r\n" . "X-Forwarded-For: 47.".rand(1, 254).".".rand(1, 254).".".rand(1, 254)."\r\n",
                'content' => $query
            ];
            if ($method == 'GET')
            {
                $result = file_get_contents($url . '?' . $query);
            } else {
                $context = stream_context_create($options);
                $result = file_get_contents($url, false, $context);
            }
            $data = json_decode($result, true);
            if (!$data)
            {
                throw new \Exception('data null');
            }
            return $data;
        } catch (\Exception $e) {
            sleep(3);
            echo "HTTP ERROR:" . $e->getMessage() . PHP_EOL;
            return $this->request($url, $params, $method);
        }
    }

    private function diagram()
    {
        while (true) {
            $picData = DB::table('year_pics')->select('pictureName', 'year', 'max_issue', 'pictureTypeId', 'lotteryType')->whereIn('lotteryType', [1, 2])->where('year', date('Y'))->orderByDesc('id')->get();
            if (!$picData)
            {
                echo "Data not find!" . PHP_EOL;
                sleep(60*60);
                continue;
            }
            foreach ($picData as $item) {
                echo $item->pictureName . PHP_EOL;
//                $item->max_issue = '124';
//                $item->pictureTypeId = '12690';
                $list = $this->request(self::$host . '/unite49/h5/speak/speakList', [
                    'pictureId' => $item->year . $item->max_issue . $item->pictureTypeId,
                    'pageNum' => 1,
                    'pageSize' => 1000], 'POST');
                if ($list['subCode'] == 10000 && count($list['data']) > 0) {
                    $insDiagram = [];
                    foreach ($list['data']['list'] as $value) {
                        if (DB::table('pic_diagrams')->where('source_id', $value['id'])->exists()) {
                            break;
                        } else {
                            $picDetailId = DB::table('pic_details')->where('pictureId', $item->year . $item->max_issue . $item->pictureTypeId)->value('id');
                            if (!$picDetailId) {
                                continue;
                            }
                            array_unshift($insDiagram, [
                                'user_id' => $this->getUserid($value['nickname']),
                                'pic_detail_id' => $picDetailId,
                                'issue' => $item->max_issue,
                                'lotteryType' => $item->lotteryType,
                                'title' => $value['title'],
                                'content' => strip_tags($value['content'], '<p><br><i><b><hr>'),
                                'status' => 1,
                                'source_id' => $value['id'],
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                    if (count($insDiagram) > 0) {
                        foreach ($insDiagram as $insItem) {
                            DB::table('pic_diagrams')->insert($insItem);
                        }
                        echo '采集入库：' . count($insDiagram) . '条' . PHP_EOL;
                    }
                }
                sleep(1);
            }
            sleep(60*60*2);
        }
    }

    private function getUserid($username)
    {
        $checkExists = DB::table('users')->where('account_name', $username)->value('id');
        if ($checkExists) {
            return $checkExists;
        }
        $insUser = [
            'name' => $username,
            'nickname' => $username,
            'account_name' => $username,
            'avatar' => AuthConfig::with('avatar')->first()->avatar->url,
            'new_avatar' => AuthConfig::with('avatar')->first()->avatar->url,
            'password' => bcrypt('Aa123321.'),
            'invite_code' => $this->randString(),
            'register_at' => date('Y-m-d H:i:s'),
            'last_login_at' => date('Y-m-d H:i:s'),
            'sex' => 1,
            'system' => 1,
        ];
        return DB::table('users')->insertGetId($insUser);
    }

    /**
     * 生成邀请码
     * @return string
     */
    protected function randString():string
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d').substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for (
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 8;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }

}
