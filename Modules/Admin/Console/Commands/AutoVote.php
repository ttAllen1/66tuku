<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Models\IndexPic;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AutoVote extends Command
{

    protected $_issue;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:auto-vote';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '自动给图片详情投票';

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
        DB::beginTransaction();
//        $this->voteNum();
        try{
            $year = date('Y');
            $lotteryTypes = [1, 2, 3, 4, 6];
            foreach ($lotteryTypes as $v) {
                $this->_issue[$v] = (new BaseService())->getNextIssue($v);
            }
            $pictureTypeId = DB::table('index_pics')->where('sort', '<=', 50)->pluck('pictureTypeId')->toArray();
            foreach ($lotteryTypes as $lotteryType) {
                $ids = DB::table('pic_details')->where('year', $year)->where('lotteryType', $lotteryType)->whereIn('pictureTypeId', $pictureTypeId)->where('issue', $this->_issue[$lotteryType])->pluck('id')->toArray();
                DB::table('votes')->whereIn('voteable_id', $ids)->where('voteable_type', 'Modules\Api\Models\PicDetail')->delete();
                $data = [];
//            dd($ids);
                foreach($ids as $k => $id) {
                    $res = $this->voteNum();
                    $data[$k]['voteable_id'] = $id;
                    $data[$k]['voteable_type'] = 'Modules\Api\Models\PicDetail';
                    $data[$k]['sx_niu'] = $res['sx_niu'];
                    $data[$k]['sx_ma'] = $res['sx_ma'];
                    $data[$k]['sx_yang'] = $res['sx_yang'];
                    $data[$k]['sx_ji'] = $res['sx_ji'];
                    $data[$k]['sx_gou'] = $res['sx_gou'];
                    $data[$k]['sx_zhu'] = $res['sx_zhu'];
                    $data[$k]['sx_shu'] = $res['sx_shu'];
                    $data[$k]['sx_hu'] = $res['sx_hu'];
                    $data[$k]['sx_tu'] = $res['sx_tu'];
                    $data[$k]['sx_long'] = $res['sx_long'];
                    $data[$k]['sx_she'] = $res['sx_she'];
                    $data[$k]['sx_hou'] = $res['sx_hou'];
                    $data[$k]['total_num'] = 200;
                    $data[$k]['created_at'] = date('Y-m-d H:i:s');
                }
//                dd($data);
                DB::table('votes')->insert($data);

            }
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
        }

    }

    public function voteNum()
    {
        // 定义 12 个生肖
        $sx_12 = ["牛", "马", "羊", "鸡", "狗", "猪", "鼠", "虎", "兔", "龙", "蛇", "猴"];
        $_vote_zodiac = [
            '鼠' => 'sx_shu',
            '牛' => 'sx_niu',
            '虎' => 'sx_hu',
            '兔' => 'sx_tu',
            '龙' => 'sx_long',
            '蛇' => 'sx_she',
            '马' => 'sx_ma',
            '羊' => 'sx_yang',
            '猴' => 'sx_hou',
            '鸡' => 'sx_ji',
            '狗' => 'sx_gou',
            '猪' => 'sx_zhu',
        ];

        // 生成 12 个随机整数，代表每个生肖的票数
        $randomVotes = [];
        $totalVotes = 200;
        for ($i = 0; $i < count($sx_12)-1; $i++) {
            // 生成一个随机票数，范围在 0 到剩余票数之间
            $randomVote = rand(0, floor($totalVotes*.3));
            // 将随机票数添加到结果数组中
            $randomVotes[$sx_12[$i]] = $randomVote;
            // 更新剩余票数
            $totalVotes -= $randomVote;
        }
        $randomVotes[$sx_12[11]] = $totalVotes;

        // 输出随机分配的票数
        $data = [];
//        dd($randomVotes);
        foreach ($randomVotes as $sx => $votes) {
            $data[$_vote_zodiac[$sx]]['name'] = $sx;
            $data[$_vote_zodiac[$sx]]['percentage'] = number_format(number_format($votes / 200, 2) * 100, 2) ;
            $data[$_vote_zodiac[$sx]]['sx'] = $_vote_zodiac[$sx];
            $data[$_vote_zodiac[$sx]]['vote_num'] = $votes;
//            echo $sx . ": " . $votes . "票" . PHP_EOL;
        }
//        sort($data);
        foreach($data as $k => $v) {
            $data[$k] = json_encode($v);
        }
        return $data;
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
