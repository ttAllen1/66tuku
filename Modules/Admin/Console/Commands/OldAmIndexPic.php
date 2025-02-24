<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class OldAmIndexPic extends Command
{
    static $sort_config = [
        'color' => 1, // 1彩色 2黑白
        'sort'  => 1
    ];
    protected $_typeIds = [];


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:old-am-index-pic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        return ;
        $res = YearPic::query()->where('lotteryType', 7)->orderBy('id')->get()->toArray();
        $data = [];
        $i = 1;
        foreach ($res as $k => $v) {
            $data['lotteryType'] = 7;
            $data['pictureTypeId'] = $v['pictureTypeId'];
            $data['pictureName'] = $v['pictureName'];
            $data['series_id'] = 0;
            $data['color'] = $v['color'];
            $data['sort'] = $i;
            $data['is_add'] = 2;
            $data['created_at'] = date('Y-m-d H:i:s');
            DB::table('index_pics')->insert($data);
            $i++;
            echo "正在执行第" . ($k + 1) . '条数据' . PHP_EOL;
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
