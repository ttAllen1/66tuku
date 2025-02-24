<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\IndexPic;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SpiderComments extends Command
{

    protected $_url = 'https://49208c.com/unite49/h5/comment/listLatest?relatedId=';
    protected $_year = null;
    protected $_inner_users = [];
    protected $_pic_ids = [
        [
            'lotteryType'=>2,
            'picTypeId' => 28089
        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:spider-comment';     // 5分钟执行一次

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '采集指定图片的评论数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_year = date('Y');
        $this->_inner_users = $this->getInnerUsers();

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $list = $this->getSpiderUrlList();
        foreach($list as $k => $v) {
            $response = Http::withOptions([
                'verify'=>false
            ])->get($v['url']);
            if ($response->status() != 200) {
                Log::error('命令【module:spider-comment】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                exit('终止此次');
            }
            $res = json_decode($response->body(), true);

            if ($res['code'] != 10000 || empty($res['data'])) {
                continue;
            }
            $data = $res['data']['list'];
            $createdData = [];
            $picDetailId = 0;
            foreach($data as $key => $datum) {
                $createTime = $datum['createTime'] / 1000;
                if (Carbon::createFromTimestamp($createTime)->diffInMinutes(Carbon::now())>30) {
                    continue;
                }
//                dd($data, $datum, $createTime, Carbon::createFromTimestamp($createTime)->diffInMinutes(Carbon::now()));
                if (!$picDetailId = DB::table('pic_details')->where('pictureId', $v['pictureId'])->value('id')) {
                    continue;
                }
                $createdData[$key]['content'] = preg_replace('/[a-zA-Z]/', '', $datum['content']);
                $createdData[$key]['thumbUpCount'] = rand(0, 10);
                $createdData[$key]['user_id'] = $this->_inner_users[array_rand($this->_inner_users)];
                $createdData[$key]['commentable_id'] = $picDetailId;
                $createdData[$key]['commentable_type'] = 'Modules\Api\Models\PicDetail';
                $createdData[$key]['created_at'] = Carbon::createFromTimestamp(rand(now()->subMinutes(10)->timestamp, now()->timestamp))->format('Y-m-d H:i:s');
            }

            if ($createdData) {
                DB::table('user_comments')->insert($createdData);
                DB::table('pic_details')->where('id', $picDetailId)->increment('commentCount', count($createdData));
            }

        }

    }

    private function getSpiderUrlList()
    {
        $list = [];
        foreach ($this->_pic_ids as $k => $v) {
            // 获取最新期数
            $list[$k]['pictureId'] =$this->_year . (new BaseService())->getNextIssue($v['lotteryType']) . $v['picTypeId'];
            $list[$k]['url'] =$this->_url . $this->_year . (new BaseService())->getNextIssue($v['lotteryType']) . $v['picTypeId']. '&type=1&pageSize=10&pageNum=1&postUserId=&sort=2&lotteryType=' . $v['lotteryType'];
        }
        // https://49208c.com/unite49/h5/comment/listLatest?relatedId=202427828089&type=1&pageSize=10&pageNum=17&postUserId=&sort=2&lotteryType=2
        return $list;
    }

    private function getInnerUsers()
    {
//        if ($res = Redis::get('inner_users')) {
//            return json_decode($res, true);
//        }
        $res = DB::table('users')
            ->where('is_chat', 1)
            ->pluck('id')
            ->toArray();
//        Redis::setex('inner_users', 3600*24, json_encode($res));
        return $res;
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
