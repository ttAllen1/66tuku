<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\Mystery;
use Symfony\Component\Console\Input\InputOption;

class MysteryTipsAm extends Command
{

    protected $_years = [2023];

    protected $_period_list_url = 'https://api.xyhzbw.com/unite49/h5/tool/listSinkBag?jpushId=75987&year=%d'; // 75987
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:mystery-tips-am';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '每天一次：玄机锦囊历史数据并支持获取最新一期数据.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        if(date('Y') != 2023) {
            $this->_years = range(2023, date('Y'));
        }

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
        $year = date('Y');
        $res = Mystery::query()->select(['content'])->where('lotteryType', '<>', 5)->orderByRaw('RAND()')->take(1)->get();
        $data = [];
        $realOpen  = Redis::get('real_open_5');
        $issue = explode(',', $realOpen)[8];

        foreach ($res as $k => $content) {
            $data[$k]['year'] = $year;
            $data[$k]['issue'] = $issue;
            $data[$k]['lotteryType'] = 5;
            $data[$k]['title'] = $year.'年第'.$issue.'期六合彩';
            $data[$k]['content'] = json_encode($content['content']);
            $data[$k]['created_at'] = date('Y-m-d H:i:s');
        }

        $this->db($data);
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
