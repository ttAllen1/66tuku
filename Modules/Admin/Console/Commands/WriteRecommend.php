<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\HistoryNumber;
use Symfony\Component\Console\Input\InputOption;

class WriteRecommend extends Command
{
    protected $_lotteryTypes = [1,2,4];

    protected $_period_list_url = 'https://49208.com/unite49/h5/index/lastLotteryRecord?lotteryType=%d';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:write-recommend';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '爬取49开奖推荐数据.';

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
            if (!Redis::get('lottery_real_open_over_'.$lotteryType.'_with_'.date('Y-m-d'))) {
                continue;
            }
            $this->do($lotteryType);
        }
    }

    public function do($lotteryType)
    {
        try {
            $response = Http::withOptions([
                'verify'=>false,
            ])->withHeaders([
                'Lotterytype' => $lotteryType,
                'User-Agent' => 'Chrome/49.0.2587.3',
                'Accept' => '*',
            ])->get(sprintf($this->_period_list_url, $lotteryType), [
                'cache_buster' => uniqid()
            ]);
            if($response->status() != 200) {
                Log::error('命令【module:write-recommend】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                return;
            }
            $res = json_decode($response->body(), true);
//            dd($res);
            $response->close();
            if($res['code'] == 10000 && !empty($res['data']['recommendList'])) {
                for ($i=0; $i<=2; $i++) {
                    $period_list[] = $res['data']['recommendList'][$i];
                }
                $data = [];
                foreach ($period_list as $k => $v) {
                    $res = HistoryNumber::query()->where('year', $v['year'])->where('lotteryType', $lotteryType)->where('issue', str_pad($v['period'], 3, 0, STR_PAD_LEFT))->select(['id', 'number', 'attr_sx'])->first();

                    $data[$k]['year'] = $v['year'];
                    $data[$k]['issue'] = $v['period'];
                    $data[$k]['lotteryType'] = $lotteryType;
                    $data[$k]['nine_xiao'] = implode(' ', $v['detailList'][0]['valueList']);
                    $data[$k]['six_xiao'] = implode(' ', $v['detailList'][1]['valueList']);
                    $data[$k]['four_xiao'] = implode(' ', $v['detailList'][2]['valueList']);
                    $data[$k]['one_xiao'] = implode(' ', $v['detailList'][3]['valueList']);
                    $data[$k]['ten_ma'] = implode(' ', $v['detailList'][4]['valueList']);
                    $data[$k]['nine_is_win'] = $v['detailList'][0]['right'];
                    $data[$k]['six_is_win'] = $v['detailList'][1]['right'];
                    $data[$k]['four_is_win'] = $v['detailList'][2]['right'];
                    $data[$k]['one_is_win'] = $v['detailList'][3]['right'];
                    $data[$k]['te_is_win'] = $v['detailList'][4]['right'];
                    $data[$k]['created_at'] = date('Y-m-d H:i:s');
                    if ($res) {
                        $data[$k]['history_id'] = $res['id'];
                    }
                }
            } else {
                return false;
            }

            $this->db($data);
        }catch (\Exception $exception) {
            return;
        }
    }

    public function db($guessList)
    {
        foreach ($guessList as $k => $v) {
            $res = DB::table('number_recommends')->updateOrInsert(
                ['year'=> $v['year'], 'issue'=> $v['issue'],'lotteryType'=> $v['lotteryType']],
                $v
            );
        }
//        DB::table('number_recommends')
//            ->upsert($guessList, ['year', 'lotteryType', 'issue'], ['updated_at']);
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
