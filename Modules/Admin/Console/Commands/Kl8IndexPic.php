<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\Kuaile;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Kl8IndexPic extends Command
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
    protected $signature = 'module:kl8-index-pic';       // 理论上一次性执行即可

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
        //        $res = [];
//        for($i=1; $i<=81; $i++) {
//            $res[$i-1] = "第".str_pad($i, 3, 0, STR_PAD_LEFT).'期';
//        }
//        rsort($res);
//        dd($res, json_encode($res));
        // 将 index_pic 的lottery=5 复制一份 lottery=6
//        INSERT INTO `lot_index_pics`( `lotteryType`, `pictureTypeId`, `pictureName`, `series_id`, `color`, `keyword`, `letter`, `sort`, `width`, `height`, `created_at`) select 6, `pictureTypeId`, `pictureName`, `series_id`, `color`, `keyword`, `letter`, `sort`, `width`, `height`, `created_at` from lot_index_pics where lotteryType = 5
        // 2024 81期开始
        $res = YearPic::query()->where('lotteryType', 2)->where('is_add', 2)->orderBy('id')->get()->toArray();
        $data = [];
        $i = 10;
        foreach ($res as $k => $v) {
            $data['lotteryType'] = 2;
            $data['pictureTypeId'] = $v['pictureTypeId'];
            $data['pictureName'] = $v['pictureName'];
            $data['series_id'] = 0;
            $data['color'] = $v['color'];
            $data['sort'] = $i;
            $data['is_add'] = 2;
//            dd($data);
            DB::table('index_pics')->insert($data);
            $i++;
            echo "正在执行第" . ($k + 1) . '条数据' . PHP_EOL;
        }
    }

    public function getPictureTypeId()
    {
        $typeId = rand(60000, 69999);
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

    function pinyin1($zh)
    {
        $ret = "";
//        $s1 = iconv("UTF-8","gb2312", $zh);
//        $s2 = iconv("gb2312","UTF-8", $s1);
//        if($s2 == $zh) {
//            $zh = $s1;
//        }

        $i = 0;
        $s1 = substr($zh, $i, 1);
        $p = ord($s1);
//        dd($p);
        if ($p > 160) {
            $s2 = substr($zh, 0, 3);
//            dd($zh, $s2);
            $ret .= $this->getfirstchar($s2);
        } else {
            $ret .= $s1;
        }
        //echo $ret."<br/>";

        return $ret;
    }

    function getfirstchar($s0)
    {
//        dd($s0);
        try {
            $fchar = ord($s0[0]);
            if ($fchar >= ord("A") and $fchar <= ord("z")) return strtoupper($s0[0]);
            $s1 = iconv("UTF-8", "gb2312", $s0);
            $s2 = iconv("gb2312", "UTF-8", $s1);
            if ($s2 == $s0) {
                $s = $s1;
            } else {
                $s = $s0;
            }
            $asc = ord($s[0]) * 256 + ord($s[1]) - 65536;
            if ($asc >= -20319 and $asc <= -20284) return "A";
            if ($asc >= -20283 and $asc <= -19776) return "B";
            if ($asc >= -19775 and $asc <= -19219) return "C";
            if ($asc >= -19218 and $asc <= -18711) return "D";
            if ($asc >= -18710 and $asc <= -18527) return "E";
            if ($asc >= -18526 and $asc <= -18240) return "F";
            if ($asc >= -18239 and $asc <= -17923) return "G";
            if ($asc >= -17922 and $asc <= -17418) return "I";
            if ($asc >= -17417 and $asc <= -16475) return "J";
            if ($asc >= -16474 and $asc <= -16213) return "K";
            if ($asc >= -16212 and $asc <= -15641) return "L";
            if ($asc >= -15640 and $asc <= -15166) return "M";
            if ($asc >= -15165 and $asc <= -14923) return "N";
            if ($asc >= -14922 and $asc <= -14915) return "O";
            if ($asc >= -14914 and $asc <= -14631) return "P";
            if ($asc >= -14630 and $asc <= -14150) return "Q";
            if ($asc >= -14149 and $asc <= -14091) return "R";
            if ($asc >= -14090 and $asc <= -13319) return "S";
            if ($asc >= -13318 and $asc <= -12839) return "T";
            if ($asc >= -12838 and $asc <= -12557) return "W";
            if ($asc >= -12556 and $asc <= -11848) return "X";
            if ($asc >= -11847 and $asc <= -11056) return "Y";
            if ($asc >= -11055 and $asc <= -10247) return "Z";
            return '#';
        } catch (\Exception $exception) {
            return '#';
        }
    }

    public function wash_data($lists, $pageNum)
    {
        $data = [];
        if ($lists[0]['color'] != self::$sort_config['color']) {
            self::$sort_config['color'] = $lists[0]['color'];
            self::$sort_config['sort'] = 1;
        }

        foreach ($lists as $k => $list) {
            $data[$k]['lotteryType'] = 5;
            $data[$k]['pictureTypeId'] = substr($list['picture_id'], 7);
            $data[$k]['pictureName'] = $list['name'];
            $data[$k]['color'] = $list['color'];
            $data[$k]['sort'] = self::$sort_config['sort']++;
//            $images = (new PictureService())->getImageInfoWithOutHttp($list['image_path']);
            $data[$k]['width'] = 0;
            $data[$k]['height'] = 0;
            $data[$k]['created_at'] = date('Y-m-d');
        }
//        if ($pageNum==2) {
//            dd($data);
//        }
//        dd($data);
        try {
            IndexPic::query()->insert($data);
        } catch (\Exception $exception) {
            dd($data, $exception->getMessage());
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
