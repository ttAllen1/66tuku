<?php

namespace Modules\Admin\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputOption;

class TongbuDb extends Command
{
    protected $_history = false; // 是否爬取历史数据 第一次开启  后面每天只爬取最新一期的数据 可关闭

    protected $_years = [];

    protected $_lotteryTypes = [2, 6, 7];


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:year-pic-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每年最后期.';

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
        foreach ($this->_lotteryTypes as $lotteryType) {
            $res = YearPic::query()->where('lotteryType', $lotteryType)->where('year', 2024)
                ->get()->toArray();
            if (in_array($lotteryType, [1, 2, 3, 5, 6, 7])) {
                foreach($res as $k => $v) {
                    unset($res[$k]['id']);
                    $res[$k]['issues'] = json_encode(["第1期"]);
                    $res[$k]['max_issue'] = 1;
                    $res[$k]['year'] = 2025;
                    $res[$k]['created_at'] = date("Y-m-d H:i:s");
                }
            } else {
                foreach($res as $k => $v) {
                    unset($res[$k]['id']);
                    $res[$k]['issues'] = json_encode(["第".$v['max_issue']."期"]);
                    $res[$k]['year'] = 2025;
                    $res[$k]['created_at'] = date("Y-m-d H:i:s");
                }
            }

//            DB::table('year_pics')->insert($res);
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
