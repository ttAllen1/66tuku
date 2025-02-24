<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\IndexPic;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class TgSend extends Command
{
    protected $_seng_imgs = [7]; // 2, 7

    protected $token;
    protected $apiUrl;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:tg-img-send';       // 理论上一次性执行即可

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发送图片到tg';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        ini_set('memory_limit', '512M');
        parent::__construct();
        $this->token = env('TELEGRAM_BOT_TOKEN'); // 从配置中获取 token
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try{
            foreach ($this->_seng_imgs as $lottery) {
                // 拿到最新的期数
                $issue = (new BaseService())->getNextIssue($lottery)-1;
                // 随机查询15张图片拼接图片地址
                $keywords = DB::table('year_pics')
                    ->where('lotteryType', $lottery)
                    ->where('color', 1)
                    ->where('year', date('Y'))
                    ->inRandomOrder()
                    ->limit(10)
                    ->get(['pictureName', 'keyword'])
                    ->map(function($item) {
                        return (array)$item;
                    })
                    ->toArray();
                $images = [];
                foreach($keywords as $k => $v) {
                    $images[$k]['media'] = 'https://amtk.tuku.fit/galleryfiles/system/big-pic/col/2024/'.$issue.'/'.$v['keyword'].'.jpg';
                    $images[$k]['caption'] = $v['pictureName'];
                    $images[$k]['type'] = 'photo';
                }
//                dd($images);
                // 检测图片是否已存在
                // 发送
                $response = Http::post("{$this->apiUrl}/sendMediaGroup", [
                    'chat_id' => -1002453327598, // 替换为你的 chat_id
                    'media' => json_encode($images),
                ]);
            }
        }catch (\Exception $exception) {
            dd($exception->getMessage());
        }
        return $response->json();
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
