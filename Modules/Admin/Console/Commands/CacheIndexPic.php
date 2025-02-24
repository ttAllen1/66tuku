<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\IndexPic;
use Modules\Api\Services\picture\PictureService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use function Symfony\Component\String\s;

class CacheIndexPic extends Command
{

    private $_lotteryTypes = [1, 2, 3, 4,5];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:cache-index-pic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '缓存49图库的首页图片到有序集合';

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
        foreach($this->_lotteryTypes as $lotteryType) {
            $cache_name = 'cache_index_pic_'.$lotteryType;
            for ($i=1; $i<=20; $i++) {
                $list = (new PictureService())->get_page_list(['page'=>$i, 'lotteryType'=>$lotteryType, 'cache'=>true]);
                Redis::del($cache_name);
                Redis::zadd($cache_name, $i, json_encode($list));
                echo '正在缓存 '.$lotteryType." 第".$i.'页 数据'.PHP_EOL;
            }
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
