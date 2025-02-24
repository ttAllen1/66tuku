<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Models\HistoryNumber;
use Modules\Admin\Models\User;
use Modules\Admin\Models\UserChat;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateChatAvatar extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:update-chat-avatar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '更新聊天默认图像.';

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
        User::query()->select(['nickname', 'id'])->where('nickname', 'like', '48图库%')->orderBy('id')->chunk(100, function($list) {
            foreach ($list as $k => $v) {
                $data = $v->toArray();

                $data['nickname'] = str_replace('48', '49', $data['nickname']);

                User::query()->where('id', $v['id'])->update(['nickname'=>$data['nickname']]);
            }
        });

//        UserChat::query()->select(['from', 'id'])->orderBy('id')->chunk(100, function($list) {
//            foreach ($list as $k => $v) {
//                $data = $v->toArray();
//                $data['from']['user_name'] = str_replace('/upload/images/20230824/KZCAogVp9oBNbKN6iClHRyjKHx5sXSXTzndKOk20.png', '/upload/images/20231119/pp3762zWBp25yOMvHlatvXFkgdVZR382TwklkYje.jpg', $data['from']['avatar']);
//
//                UserChat::query()->where('id', $v['id'])->update(['from'=>json_encode($data['from'])]);
//            }
//        });

        UserChat::query()->select(['from', 'to', 'id'])->orderBy('id')->chunk(100, function($list) {
            foreach ($list as $k => $v) {
                $data = $v->toArray();

                $data['from']['user_name'] = str_replace('48', '49', $data['from']['user_name']);
                if ($data['to']) {
                    $data['to']['to_name'] = str_replace('48', '49', $data['to']['to_name']);
                }
//                dd($data, json_encode($data['to']));
                UserChat::query()->where('id', $v['id'])->update(['from'=>json_encode($data['from']), 'to'=>json_encode($data['to'])]);
            }
        });
    }

    private function wash_data($res)
    {
        $data = [];
        $recordList = $res['data']['recordList'];
        $date = date('Y-m-d H:i:s');
        foreach ($recordList as $k => $v) {
            $data[$k]['year'] = $v['year'];
            $data[$k]['issue'] = $v['period']<10 ? '0'.$v['period'] : ($v['period']<100 ? '00'.$v['period'] : $v['period']);
            $data[$k]['lotteryType'] = $v['lotteryType'];
            $data[$k]['lotteryTime'] = $this->date_format($v['lotteryTime']);
            $data[$k]['lotteryWeek'] = $this->week_format($data[$k]['lotteryTime']);
            $data[$k]['number'] = implode(' ', array_column($v['numberList'], 'number'));
            $data[$k]['attr_sx'] = implode(' ', array_column($v['numberList'], 'shengXiao'));
            $data[$k]['attr_wx'] = implode(' ', array_column($v['numberList'], 'wuXing'));
            $data[$k]['attr_bs'] = implode(' ', array_column($v['numberList'], 'color'));
            $data[$k]['number_attr'] = json_encode($v['numberList']);
            $data[$k]['te_attr'] = json_encode($this->spi_data(array_slice($v['numberList'], -1, 1)[0]));
            $data[$k]['total_attr'] = json_encode($this->total_data(array_column($v['numberList'], 'number')));
            $data[$k]['created_at'] = $date;
        }

        $res = HistoryNumber::query()->upsert($data, ['year', 'issue', 'lotteryType']);

    }

    public function date_format($date)
    {
        $date = str_replace('年', '-', $date);
        $date = str_replace('月', '-', $date);
        $date = str_replace('日', '', $date);
        return $date;
    }

    public function week_format($date)
    {
        $week = date('w', strtotime($date));
        switch ($week){
            case 0:
                $w = '日';
                break;
            case 1:
                $w = '一';
                break;
            case 2:
                $w = '二';
                break;
            case 3:
                $w = '三';
                break;
            case 4:
                $w = '四';
                break;
            case 5:
                $w = '五';
                break;
            case 6:
            default:
                $w = '六';
                break;
        }

        return $w;
    }

    public function spi_data($numData)
    {
        $numData['oddEven'] = $numData['number'] % 2 == 0 ? '双' : '单';
        $numData['bigSmall'] = $numData['number'] >=25 ? '大' : '小';
        return $numData;
    }

    public function total_data($numData)
    {
        $data['total'] = array_sum($numData);
        $data['oddEven'] = $data['total'] % 2 == 0 ? '双' : '单';
        $data['bigSmall'] = $data['total'] >= 175 ? '大' : '小';
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
