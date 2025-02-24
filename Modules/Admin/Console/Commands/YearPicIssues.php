<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Swoole\Process;

class YearPicIssues extends Command
{
    protected $_history = false;        // true 初始化 更新全部历史信息的最小其实 false 只更新当前年的最小期数 理论上一年执行一次即可

    protected $ly = [];
    protected $_url = "https://api.xyhzbw.com/unite49/h5/picture/listPeriod?pictureTypeId=%d&year=%d";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:year-pic-issues'; // 被废弃 详情页直接判断

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '补充信息：更新表year_pic的最小期数值 ';

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
        try {
            $current_year = date('Y');
            $pictureInfos = YearPic::query()
                ->when(!$this->_history, function($query) use ($current_year) {
                    $query->where('year', $current_year);
                })
                ->orderBy('id', 'asc')
                ->get(['pictureTypeId', 'year', 'id'])->toArray();
//        dd($pictureInfos);
            $process_num = 10;
            $chunkSize = ceil(count($pictureInfos) / $process_num);

            for ($i=0; $i<$process_num; $i++) {
                $start = $i * $chunkSize;
                $end = ($i+1)  * $chunkSize;
                $process = new Process(function () use($start, $end, $pictureInfos){
                    $pid = getmypid();
                    $this->processDo($start, $end, $pictureInfos, $pid);
                });
                $process->start();
            }

            for ($n = $process_num; $n--;) {
                $status = Process::wait(true);
                echo '-------------------------' . PHP_EOL;
                echo "Recycled #{$status['pid']}, code={$status['code']}, signal={$status['signal']}" . PHP_EOL;
            }
            echo 'Parent #' . getmypid() . ' exit' . PHP_EOL;
        }catch (\Exception $exception) {

        }
    }

    function processDo($start, $end, $pictureInfos, $pid)
    {
        try {
            for ($i=$start; $i<=$end; $i++) {
                $response = Http::withOptions([
                    'verify'=>false,
                    'headers' => [
                        'Connection' => 'keep-alive', // 设置连接头为 'keep-alive'，保持长连接
                    ],
                ])->get(sprintf($this->_url, $pictureInfos[$i]['pictureTypeId'], $pictureInfos[$i]['year']));
                if ($response->status() != 200) {
                    Log::error('命令【module:pic-info-other】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                    continue;
                }
                $res = json_decode($response->body(), true);
                if (!$res['data'] || !$res['data']['periodList']) {
                    continue;
                }
                $names =  array_column($res['data']['periodList'], 'name');
                $maxIssue = ltrim($names[0], '第');
                $maxIssue = rtrim($maxIssue, '期');
                DB::table('year_pics')->where('id', $pictureInfos[$i]['id'])->update([
                    'issues'    => json_encode($names),
                    'max_issue' => $maxIssue
                ]);
                sleep(1);
                echo "进程ID {$pid} 正在执行 ：".$pictureInfos[$i]['year'].'年的'.$pictureInfos[$i]['pictureTypeId'].PHP_EOL;
            }
        }catch (\Exception $exception) {
            $this->ly[] = ['pictureTypeId' => $pictureInfos[$i]['pictureTypeId'], 'year' => $pictureInfos[$i]['year']];
            throw new \Exception('pictureTypeId：'.$pictureInfos[$i]['pictureTypeId']);
        }

    }

    public function wash_data($res)
    {
        $data = [];
        foreach ($res['data']['list'] as $k => $items) {
            $data[$k]['year']               = $res['data']['year'];
            $data[$k]['color']              = $res['data']['color'];
            $data[$k]['pictureTypeId']      = $items['pictureTypeId'];
            $data[$k]['lotteryType']        = $items['lotteryType'];
            $data[$k]['max_issue']          = $items['number'];
            $data[$k]['keyword']            = $items['keyword'];
            $data[$k]['letter']             = $items['letter'];
            $data[$k]['pictureName']        = $items['pictureName'];
            $data[$k]['created_at']         = date('Y-m-d');
        }

        if ($this->_history) {
            YearPic::query()->insert($data);
        } else {
            YearPic::query()->upsert($data, ['year', 'pictureTypeId'], ['max_issue']);
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
}
