<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\Kuaile;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class OldAmYearPic extends Command
{
    static $sort_config = [
        'color' => 1, // 1彩色 2黑白
        'sort'  => 1
    ];
    protected $_typeIds = [];
    protected $_issues = '["\u7b2c204\u671f","\u7b2c203\u671f","\u7b2c202\u671f"]';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:old-am-year-pic';       // 理论上一次性执行即可

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将49图库的首页图片信息写库，方便调用.执行一次即可';

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
        $res = YearPic::query()
            ->where('lotteryType', 2)
            ->where('is_add', 2)
            ->where('year', 2024)
            ->orderBy('id')
            ->get()->toArray();
        $data = [];
        foreach ($res as $k => $v) {
            // 判断keyword在year_pic是否存在
            $data['pictureTypeId'] = $this->getPictureTypeId();
            $data['lotteryType'] = 7;
            $data['year'] = 2024;
            $data['color'] = $v['color'];
            $data['pictureName'] = $v['pictureName'];
            $data['max_issue'] = 204;
            $data['issues'] = $this->_issues;
            $data['keyword'] = $v['keyword'];
            $data['letter'] = $v['letter'];
            $data['is_add'] = 2;
            $data['created_at'] = date('Y-m-d H:i:s');
//            dd($data);
            DB::table('year_pics')->insert($data);
            echo "正在执行第" . ($k + 1) . '条数据' . PHP_EOL;
        }

//        $typeIds = DB::table('year_pics')->where('lotteryType', 2)->where('is_add', 2)->where('year', 2024)->pluck('pictureTypeId')->toArray();
//        $res = DB::table('lottery_type_ids')->where('lotteryType', 2)->value('typeIds');
//        dd($this->_typeIds, json_decode($res, true));
//        $res = array_merge($typeIds, json_decode($res, true));
//        DB::table('lottery_type_ids')->where('lotteryType', 2)->update([
//            'typeIds'=>json_encode($res),
//        ]);
    }

    public function getPictureTypeId()
    {
        $typeId = rand(500000, 599999);
        if (in_array($typeId, $this->_typeIds)) {
            return $this->getPictureTypeId();
        } else {
            if (!$typeId) {
                return $this->getPictureTypeId();
            }
            $this->_typeIds[] = $typeId;
            return $typeId;
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
