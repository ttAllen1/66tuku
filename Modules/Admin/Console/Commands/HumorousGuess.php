<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputOption;

class HumorousGuess extends Command
{
    protected $_history = false; // 是否爬取历史数据 第一次开启  后面每天只爬取最新一期的数据 可关闭

    protected $_years = [2020, 2021, 2022, 2023];

    protected $_lotteryTypes = [1, 2, 3, 4];

    protected $_period_list_url = 'https://49208.com/unite49/h5/guess/listPeriod?year=%s';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:humorous-guess';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取历史幽默竞猜数据支持每日更新数据.';

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
     * PHP-FPM版
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        set_time_limit(0);
        foreach ($this->_lotteryTypes as $lotteryType) {
            if ($this->_history) {
                foreach ($this->_years as $year) {
                    $this->do($lotteryType, $year);
                }
            } else {
                // todo 只拉取最新一期的猜测
                $this->do($lotteryType, date('Y'));
            }
        }
    }

    public function do($lotteryType, $year)
    {
        try {
            $response = Http::withOptions([
                'verify'=>false,
            ])->withHeaders([
                'Lotterytype' => $lotteryType,
                'User-Agent' => 'Chrome/49.0.2587.3',
                'Accept' => '*',
            ])->post(sprintf($this->_period_list_url, $year), [
                'cache_buster' => uniqid()
            ]);

            if($response->status() != 200) {
                Log::error('命令【module:humorous-guess】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                return;
            }
            $res = json_decode($response->body(), true);
            $response->close();
            if($res['code'] == 10000 && isset($res['data']) && isset($res['data']['periodList']) && !empty($res['data']['periodList'])) {
                //dd(1);
                if ($this->_history) {
                    $period_list = $res['data']['periodList'];
                } else {
                    for ($i=0; $i<=0; $i++) {
                        $period_list[$i] = $res['data']['periodList'][$i];
                    }
//                    dd($period_list);
                }
            } else {
                return false;
            }
            $i=0;
//            dd($period_list);
            foreach ($period_list as $k => $period) {
                if ($lotteryType== 3 && $period['number']>300) {
                    continue;
                }
                $i++;
                $response = Http::withOptions([
                    'verify'=>false
                ])->withHeaders([
                    'Lotterytype' => $lotteryType
                ])->post(sprintf('https://49208.com/unite49/h5/guess/detail?id=%d&reload=1', $period['guessId']), [
                    'cache_buster' => uniqid(), // 添加一个唯一的查询参数
                ]);
                $guessData = json_decode($response->body(), true);
                try{
                    if($guessData['code'] == 10000 && isset($guessData['data'])) {
                        $guessList[$k]['guessId']           = $period['guessId'];
                        $guessList[$k]['year']              = $year;
                        $guessList[$k]['lotteryType']       = $lotteryType;
                        $guessList[$k]['issue']             = $period['number'];
                        $guessList[$k]['title']             = $guessData['data']['title'] ?? '';
                        $guessList[$k]['pictureTitle']      = $guessData['data']['pictureTitle'] ?? '';
                        $guessList[$k]['pictureContent']    = $guessData['data']['pictureContent'] ?? '';
                        $guessList[$k]['imageUrl']          = $this->ImgWithoutHttp($lotteryType, $guessData['data']['pictureList'][0]['imageUrl']);
                        $guessList[$k]['width']             = $guessData['data']['pictureList'][0]['width'] ?? '';
                        $guessList[$k]['height']            = $guessData['data']['pictureList'][0]['height'] ?? '';
                        $guessList[$k]['videoTitle']        = $guessData['data']['videoTitle'] ?? '';
                        $guessList[$k]['videoContent']      = $guessData['data']['videoContent'] ?? '';
                        $guessList[$k]['videoUrl']          = $guessData['data']['videoUrl'] ?? '';
                        $guessList[$k]['created_at']        = date('Y-m-d H:i:s');
                    } else {
                        continue;
                    }

                }catch (\Exception $exception) {
                    sleep(10);
                    continue;
                }
                echo ($lotteryType==1?'港彩':($lotteryType==2?'澳彩':($lotteryType==3?'台彩':'新彩')))  . '正在执行' . $year .'年的'.$period['name'].'当前年共有'.count($period_list).'期 还剩'.(count($period_list)-$i).'期 ！'.PHP_EOL;
            }
            echo ($lotteryType==1?'港彩':($lotteryType==2?'澳彩':($lotteryType==3?'台彩':'新彩')))  . '第' . $year .'年 已全部执行完毕！'.PHP_EOL;

            $this->db($guessList);
        }catch (\Exception $exception) {
            return;
        }
    }

    public function db($guessList)
    {
        DB::table('humorous')
            ->upsert($guessList, ['year', 'lotteryType', 'issue'], ['updated_at']);
    }

    private function ImgWithoutHttp($lotteryType, $imageUrl)
    {
        if (!$imageUrl) {
            return '';
        }
        $imgPrefix = Redis::get('img_prefix');
        if (!$imgPrefix) {
            return $imageUrl;
        }
        $imgPrefix = json_decode($imgPrefix, true);
        $imgPrefix = $imgPrefix[date('Y')];

        return str_replace($imgPrefix[$lotteryType], '', $imageUrl);
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
