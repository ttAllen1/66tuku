<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\IndexPic;
use Modules\Api\Models\CorpusType;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\User;
use Modules\Api\Models\UserComment;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Smart extends Command
{
    protected $_issue;
    protected $_year;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:smart';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '智能评论';

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
        ini_set('memory_limit', '-1');
        $config = json_decode(Redis::get('chat_smart'), true);
        if ($config['comment_switch'] == 0) {
            return ;
        }
//        dd($config);
        $this->_year = date('Y');

        // 判断时间区间

        // 判断彩种
        $lotteryTypes = $config['lotteryTypes'];
        $lotteryType = [];
        if (in_array('澳彩', $lotteryTypes)) {
            $lotteryType[] = 2;
        }
        if (in_array('快乐八', $lotteryTypes)) {
            $lotteryType[] = 6;
        }
        if (in_array('港彩', $lotteryTypes)) {
            $lotteryType[] = 1;
        }
        if (in_array('台彩', $lotteryTypes)) {
            $lotteryType[] = 3;
        }
        if (in_array('新彩', $lotteryTypes)) {
            $lotteryType[] = 4;
        }
        foreach ($lotteryType as $v) {
            $this->_issue[$v] = (new BaseService())->getNextIssue($v);
        }
//        dd($this->_issue, $lotteryType, $lotteryTypes);
//        dd($lotteryType, $config);
        // 随机出指定数量机器人 随机出资料 论坛 图片
        echo "start_time: ".time().PHP_EOL;
        $this->discuss($config, $lotteryType);
        echo "time1: ".time().PHP_EOL;
        $this->picture($config, $lotteryType);
        echo "time2: ".time().PHP_EOL;
        $this->corpus($config, $lotteryType);
        echo "time3: ".time();
    }

    public function discuss($config, $lotteryTypes)
    {
        try{
            DB::beginTransaction();
            $robotIds = User::query()->where('is_chat', 1)->where('status', 1)->inRandomOrder()->limit($config['discuss_robot_nums'])->pluck('id')->toArray();
//        dd($robotIds, $lotteryTypes);
            $objNum = 10;
            $discussIds = [];
            foreach ($lotteryTypes as $lotteryType) {
                $discussIds = Discuss::query()->where('year', $this->_year)->where('lotteryType', $lotteryType)->where('status', 1)->where('issue', (int)$this->_issue[$lotteryType])->inRandomOrder()->limit($objNum)->pluck('id')->toArray();
                $data = [];
                foreach ($discussIds as $k => $v) {
                    $num = $config['discuss_robot_nums']; // 每个对象下有多少机器人评论
                    for($i=0; $i<=$num-1; $i++) {
                        $data[$k][$i]['user_id'] = $robotIds[array_rand($robotIds)];
                        $data[$k][$i]['commentable_id'] = $v;
                        $data[$k][$i]['commentable_type'] = 'Modules\Api\Models\Discuss';
                        $data[$k][$i]['content'] = $this->randomContent(1, $lotteryType);
                        $data[$k][$i]['created_at'] = $this->roundTime();
                    }
                }
                foreach ($discussIds as $k => $vv) {
                    UserComment::query()->insert($data[$k]);
                    DB::table('discusses')->where('id', $vv)->increment('commentCount', $config['discuss_robot_nums']);
                }
            }
//            if ($discussIds) {
//                // 修改原对象的评论数
//                DB::table('discusses')->whereIn('id', $discussIds)->increment('commentCount', $config['discuss_robot_nums']);
//            }
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
        }

//        dd($data);

    }

    public function picture($config, $lotteryTypes)
    {
        try{
            DB::beginTransaction();
            $robotIds = User::query()->where('is_chat', 1)->where('status', 1)->inRandomOrder()->limit($config['picture_robot_nums'])->pluck('id')->toArray();
//            $picIds = [];
            foreach ($lotteryTypes as $lotteryType) {
                $picIds = PicDetail::query()->where('year', $this->_year)->where('issue', (int)$this->_issue[$lotteryType])->where('lotteryType', $lotteryType)->inRandomOrder()->limit(10)->pluck('id')->toArray();
                $data = [];
                foreach ($picIds as $k => $v) {
                    $num = $config['picture_robot_nums']; // 每个对象下有多少机器人评论
                    for($i=0; $i<=$num-1; $i++) {
                        $data[$k][$i]['user_id'] = $robotIds[array_rand($robotIds)];
                        $data[$k][$i]['commentable_id'] = $v;
                        $data[$k][$i]['commentable_type'] = 'Modules\Api\Models\PicDetail';
                        $data[$k][$i]['content'] = $this->randomContent(2, $lotteryType);
                        $data[$k][$i]['created_at'] = $this->roundTime();
                    }
                }
                foreach ($picIds as $k => $vv) {
                    UserComment::query()->insert($data[$k]);
                    DB::table('pic_details')->where('id', $vv)->increment('commentCount', $config['picture_robot_nums']);
                }
            }
//            if ($picIds) {
//                // 修改原对象的评论数
//                DB::table('pic_details')->whereIn('id', $picIds)->increment('commentCount', $config['picture_robot_nums']);
//            }
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
        }
//        dd($data);
    }

    public function corpus($config, $lotteryTypes)
    {
        $robotIds = User::query()->where('is_chat', 1)->where('status', 1)->inRandomOrder()->limit($config['corpus_robot_nums'])->pluck('id')->toArray();
        foreach ($lotteryTypes as $lotteryType) {
            $tableInfo = CorpusType::query()->where('website', 2)->where('lotteryType', $lotteryType)->inRandomOrder()->limit(10)->select(['id', 'table_idx'])->get()->toArray();
            // 把相同表的id归纳到一起
            $info = [];
            foreach($tableInfo as $k => $v) {
                $info[$v['table_idx']][] = $v['id'];
            }
            $corpus = [];
            foreach ($info as $table => $ids) {
                $corpus[$table] = DB::table('corpus_articles'.$table)->whereIn('corpusTypeId', $ids)->whereDate('updated_at', date('Y-m-d'))->inRandomOrder()->limit(10)->pluck('id')->toArray();
            }
            $data = [];
            foreach($corpus as $table => $ids) {
                foreach ($ids as $k => $v) {
                    $num = $config['corpus_robot_nums']; // 每个对象下有多少机器人评论
                    for($i=0; $i<=$num-1; $i++) {
                        $data[$table][$k][$i]['user_id'] = $robotIds[array_rand($robotIds)];
                        $data[$table][$k][$i]['commentable_id'] = $v;
                        $data[$table][$k][$i]['commentable_type'] = 'Modules\Api\Models\CorpusArticle'.$table;
                        $data[$table][$k][$i]['content'] = $this->randomContent(3, $lotteryType);
                        $data[$table][$k][$i]['created_at'] = $this->roundTime();
                    }
                }
            }
            foreach ($data as $table => $v) {
                foreach ($v as $k => $vv) {
                    UserComment::query()->insert($vv);
                }
            }
            foreach ($corpus as $table => $v) {
                DB::table('corpus_articles'.$table)->whereIn('id', $v)->increment('commentCount', $config['corpus_robot_nums']);
            }
        }
//        dd($data, $corpus);
    }

    public function roundTime(): string
    {
        $currentTime = Carbon::now();

       // 生成随机分钟数，范围在 0 到 30 之间
        $randomMinutes = rand(0, 30);

        return $currentTime->copy()->subMinutes($randomMinutes)->format('Y-m-d H:i:s');
    }
    /**
     * 随机内容
     * 110期一肖牛
     * 10期参考10码
     * @return string
     */
    public function randomContent($type, $lotteryType)
    {
        $arr = [0, 1];
        $semantic = ['我觉得', '这期我推荐', '', '', '']; // 语意
        $issue = ($arr[array_rand($arr)] == 0 || $type == 3) ? '' : $this->_issue[$lotteryType].'期';
        return $issue . ($arr[array_rand($arr)] == 0 ? '' : ($semantic[array_rand($semantic)])) . ($this->getSx());
    }

    public function getSx()
    {
        $sx = ['一肖', '二肖', '三肖', '四肖', '五肖'];
        $sxRound = array_rand($sx);
        $sx_12 = ["牛", "马", "羊", "鸡", "狗", "猪", "鼠", "虎", "兔", "龙", "蛇", "猴"];
        $randomElements = [];
        $randomKeys = array_rand($sx_12, $sxRound+1);
        $randomKeys = is_array($randomKeys) ? $randomKeys : [$randomKeys];
        foreach ($randomKeys as $key) {
            $randomElements[] = $sx_12[$key];
        }

        return $sx[$sxRound] . implode(' ', $randomElements);
    }

    public function getNumber()
    {
        $sx = ['01', '02', '07', '08', '12', '13', '18', '19', '23', '24', '29', '30', '34', '35', '40', '45', '46', '05', '06', '11', '16', '17', '21', '22', '27', '28', '32', '33', '38', '39', '43', '44', '49', '03', '04', '09', '10', '14', '15', '20', '25', '26', '31', '36', '37', '41', '42', '47', '48'];
        $randomElements = [];
        $randomKeys = array_rand($sx, 10);
        foreach ($randomKeys as $key) {
            $randomElements[] = $sx[$key];
        }

        return '十码' . implode(' ', $randomElements);
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
