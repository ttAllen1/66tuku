<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;

class MysteryTips extends Command
{
    protected $_history = false; // 是否爬取历史数据 第一次开启  后面每天只爬取最新一期的数据 可关闭

    protected $_years = [];

    protected $_lotteryTypes = [1, 2, 3, 4];

    protected $_period_list_url = 'https://49208.com/unite49/h5/tool/listSinkBag?jpushId=75987&year=%d'; // 75987
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:mystery-tips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '玄机锦囊历史数据并支持获取最新一期数据.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_years = range(2020, date('Y'));
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
                'verify'=>false
            ])->withHeaders([
                'lotteryType' => $lotteryType
            ])->get(sprintf($this->_period_list_url, $year), [
                'cache_buster' => uniqid(), // 添加一个唯一的查询参数
            ]);
            if($response->status() != 200) {
                Log::error('命令【module:mystery-tips】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                exit('终止此次');
            }
            $res = json_decode($response->body(), true);

            if($res['code'] == 10000 && isset($res['data']) && isset($res['data']['list']) && !empty($res['data']['list'])) {
                $period_list = $res['data']['list'];
            } else {
                return false;
            }

            foreach ($period_list as $k => $period) {
                preg_match_all("/\d+/", $period['title'], $matches);
                $data[$k]['year'] = $matches[0][0];
                $data[$k]['issue'] = $matches[0][1];
                $data[$k]['lotteryType'] = $lotteryType;
                $data[$k]['title'] = $period['title'];
                $data[$k]['content'] = json_encode($period['content']);
;            }
            $this->db($data);
        }catch (\Exception $exception) {
            dd($exception->getMessage(), $exception->getLine());
        }
    }

    public function db($data)
    {
        $res = DB::table('mysteries')
            ->upsert($data, ['year', 'lotteryType', 'issue']);
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
