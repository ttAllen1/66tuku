<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\NumberRecommend;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class HistoryDataToRecommends extends Command
{
    protected const SX = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:write-recommends';       // 理论上只需要一次执行

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '补充推荐数据到历史开奖数据中.';

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

        /*
         * 初始化所有推荐数据
         * HistoryNumber::query()->orderBy('id', 'asc')
            ->select(['id', 'year', 'issue', 'lotteryType', 'number', 'attr_sx'])
            ->chunk(1, function($numbers) {
                foreach ($numbers as $number) {
                    $data[] = $this->createRecommendData($number);
                }
                $this->writeToDb($data);
            });
         */
        HistoryNumber::query()->orderBy('id', 'asc')
            ->where('lotteryType', 5)
            ->select(['id', 'year', 'issue', 'lotteryType', 'number', 'attr_sx'])
            ->chunk(100, function($numbers) {
                foreach ($numbers as $number) {
                    $data[] = $this->createRecommendData($number);
                }
                $this->writeToDb($data);
            });

//        $numbers = HistoryNumber::query()
//            ->selectRaw('max(id) as max_id')
//            ->where('year', date('Y'))
//            ->groupBy('lotteryType')
//            ->orderBy('issue', 'desc')
//            ->get();
//        $ids = [];
//        foreach ($numbers as $number) {
//            $ids[] = collect($number)->get('max_id');
//        }
//        $numbers = HistoryNumber::query()
//            ->whereIn('id', $ids)
//            ->select(['id', 'year', 'issue', 'lotteryType', 'number', 'attr_sx'])
//            ->get();
//        foreach ($numbers as $number) {
//            $data[] = $this->createRecommendData($number);
//        }
//        $this->writeToDb($data);
    }

    public function createRecommendData($number)
    {

        $te_num = substr($number['number'], -2);
        $te_sx = mb_substr($number['attr_sx'], -1);

        $data = [];
        $data['history_id']     = $number['id'];
        $data['year']           = $number['year'];
        $data['issue']          = $number['issue'];
        $data['lotteryType']    = $number['lotteryType'];
        $data['nine_xiao']      = $this->randStr();

        $data['six_xiao']       = Str::substr($data['nine_xiao'], 0, 11);
        $data['four_xiao']      = Str::substr($data['nine_xiao'], 0, 7);
        $data['one_xiao']       = Str::substr($data['nine_xiao'], 0, 1);
        $data['ten_ma']         = $this->randStr(2);
        $isWin                  = $this->getIsWin($te_sx, $data['nine_xiao']);
        $data['one_is_win']     = $isWin['one_is_win'];
        $data['four_is_win']    = $isWin['four_is_win'];
        $data['six_is_win']     = $isWin['six_is_win'];
        $data['nine_is_win']    = $isWin['nine_is_win'];
        $data['te_is_win']      = in_array($te_num, explode(' ', $data['ten_ma'])) ? 1 : 0;
        $data['created_at']     = date("Y-m-d H:i:s");

        return $data;
    }

    public function randStr($type=1)
    {
        if ($type == 1) {
            $randomKeys = array_rand(self::SX, 9);
            $sx = [];
            foreach ($randomKeys  as $key) {
                $sx[] = self::SX[$key];
            }
            return implode(' ', $sx);
        } else {
            $numbers = [];
            while (count($numbers) < 10) {
                $num = mt_rand(1, 49);
                $randomNumber = str_pad($num, 2, "0", STR_PAD_LEFT); // 生成随机数，范围从1到49
                if (!in_array($randomNumber, $numbers)) {
                    $numbers[] = $randomNumber; // 将随机数添加到数组中
                }
            }

            return implode(' ', $numbers);
        }
    }

    public function getIsWin($te_sx, $nine_xiao)
    {
        $one = Str::substr($nine_xiao, 0, 1);
        $four = Str::substr($nine_xiao, 0, 7);
        $six = Str::substr($nine_xiao, 0, 11);
        if ($te_sx == $one) {
            return ['one_is_win'=>1, 'four_is_win'=>1, 'six_is_win'=>1, 'nine_is_win'=>1];
        } else if (in_array($te_sx, explode(' ', $four))) {
            return ['one_is_win'=>0, 'four_is_win'=>1, 'six_is_win'=>1, 'nine_is_win'=>1];
        } else if (in_array($te_sx, explode(' ', $six))) {
            return ['one_is_win'=>0, 'four_is_win'=>0, 'six_is_win'=>1, 'nine_is_win'=>1];
        } else if (in_array($te_sx, explode(' ', $nine_xiao))) {
            return ['one_is_win'=>0, 'four_is_win'=>0, 'six_is_win'=>0, 'nine_is_win'=>1];
        } else {
            return ['one_is_win'=>0, 'four_is_win'=>0, 'six_is_win'=>0, 'nine_is_win'=>0];
        }
    }

    public function writeToDb($data)
    {
        NumberRecommend::query()->upsert($data, ['year', 'issue', 'lotteryType']);
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
