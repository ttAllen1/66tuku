<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\Kuaile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Kl8YearPic extends Command
{
    static $sort_config = [
        'color' => 1, // 1彩色 2黑白
        'sort'  => 1
    ];
    protected $_typeIds = [];
    protected $_issues = '["\u7b2c104\u671f","\u7b2c103\u671f","\u7b2c102\u671f","\u7b2c101\u671f","\u7b2c100\u671f","\u7b2c099\u671f","\u7b2c098\u671f","\u7b2c097\u671f","\u7b2c096\u671f","\u7b2c095\u671f","\u7b2c094\u671f","\u7b2c093\u671f","\u7b2c092\u671f","\u7b2c091\u671f","\u7b2c090\u671f","\u7b2c089\u671f","\u7b2c088\u671f","\u7b2c087\u671f","\u7b2c086\u671f","\u7b2c085\u671f","\u7b2c084\u671f","\u7b2c083\u671f","\u7b2c082\u671f","\u7b2c081\u671f","\u7b2c080\u671f","\u7b2c079\u671f","\u7b2c078\u671f","\u7b2c077\u671f","\u7b2c076\u671f","\u7b2c075\u671f","\u7b2c074\u671f","\u7b2c073\u671f","\u7b2c072\u671f","\u7b2c071\u671f","\u7b2c070\u671f","\u7b2c069\u671f","\u7b2c068\u671f","\u7b2c067\u671f","\u7b2c066\u671f","\u7b2c065\u671f","\u7b2c064\u671f","\u7b2c063\u671f","\u7b2c062\u671f","\u7b2c061\u671f","\u7b2c060\u671f","\u7b2c059\u671f","\u7b2c058\u671f","\u7b2c057\u671f","\u7b2c056\u671f","\u7b2c055\u671f","\u7b2c054\u671f","\u7b2c053\u671f","\u7b2c052\u671f","\u7b2c051\u671f","\u7b2c050\u671f","\u7b2c049\u671f","\u7b2c048\u671f","\u7b2c047\u671f","\u7b2c046\u671f","\u7b2c045\u671f","\u7b2c044\u671f","\u7b2c043\u671f","\u7b2c042\u671f","\u7b2c041\u671f","\u7b2c040\u671f","\u7b2c039\u671f","\u7b2c038\u671f","\u7b2c037\u671f","\u7b2c036\u671f","\u7b2c035\u671f","\u7b2c034\u671f","\u7b2c033\u671f","\u7b2c032\u671f","\u7b2c031\u671f","\u7b2c030\u671f","\u7b2c029\u671f","\u7b2c028\u671f","\u7b2c027\u671f","\u7b2c026\u671f","\u7b2c025\u671f","\u7b2c024\u671f","\u7b2c023\u671f","\u7b2c022\u671f","\u7b2c021\u671f","\u7b2c020\u671f","\u7b2c019\u671f","\u7b2c018\u671f","\u7b2c017\u671f","\u7b2c016\u671f","\u7b2c015\u671f","\u7b2c014\u671f","\u7b2c013\u671f","\u7b2c012\u671f","\u7b2c011\u671f","\u7b2c010\u671f","\u7b2c009\u671f","\u7b2c008\u671f","\u7b2c007\u671f","\u7b2c006\u671f","\u7b2c005\u671f","\u7b2c004\u671f","\u7b2c003\u671f","\u7b2c002\u671f","\u7b2c001\u671f"]';
    protected $_pic_configs = [
        [
            'type' => 5,
            'url'  => "https://jl.tukuapi.com/api/tuku_list?lottery_type=2&color=%d&page=%d&year=2023&is_new=1",
            'year' => [2020, 2021, 2022, 2023],
        ]
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:kl8-year-pic';       // 理论上一次性执行即可

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
        $arr =  [
            29908,
            60014,
            60015,
            20001,
            20002,
            20003,
            20004,
            20005,
            20006,
            20007,
            20008,
            20009,
            20010,
            20011,
            20012,
            20013,
            20014,
            20015,
            20016,
            20017,
            20018,
            20019,
            20020,
            20021
        ];
//        $res = DB::table('year_pics')->where('lotteryType', 2)->where('year', 2024)->where('is_add', 2)->pluck('pictureTypeId')->toArray();
//        DB::table('lottery_type_ids')->where('id', 3)->update([
//            'typeIds' => array_merge($arr, $res)
//        ]);
//        dd(1);
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
        $res = Kuaile::query()->orderBy('id')->get()->toArray();
        $data = [];
        foreach ($res as $k => $v) {
            // 判断keyword在year_pic是否存在
            $keyword = $v['picname'];
            $pictureTypeId = DB::table('year_pics')->where('lotteryType', 2)->where('keyword', $keyword)->value('pictureTypeId');
//            if ($pictureTypeId) {
//                $data['pictureTypeId'] = $pictureTypeId;
//            } else {
//                $data['pictureTypeId'] = $this->getPictureTypeId();
//            }
            $data['pictureTypeId'] = $this->getPictureTypeId();
            $data['lotteryType'] = 2;
            $data['year'] = 2024;
            $data['color'] = $v['type'] == 0 ? 1 : 2;
            $data['pictureName'] = $v['title'];
            $data['max_issue'] = 104;
            $data['issues'] = $this->_issues;
            $data['keyword'] = pathinfo($keyword, PATHINFO_FILENAME);
            $data['letter'] = $this->pinyin1($v['title']);
            $data['is_add'] = 2;
            $data['created_at'] = date('Y-m-d H:i:s');
//            dd($data);
            DB::table('year_pics')->insert($data);
            echo "正在执行第" . ($k + 1) . '条数据' . PHP_EOL;
        }
        $typeIds = DB::table('year_pics')->where('lotteryType', 2)->where('is_add', 2)->where('year', 2024)->pluck('pictureTypeId')->toArray();
        $res = DB::table('lottery_type_ids')->where('lotteryType', 2)->value('typeIds');
//        dd($this->_typeIds, json_decode($res, true));
        $res = array_merge($typeIds, json_decode($res, true));
        DB::table('lottery_type_ids')->where('lotteryType', 2)->update([
            'typeIds'=>json_encode($res),
        ]);
    }

    public function getPictureTypeId()
    {
        $typeId = rand(21000, 25999);
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
