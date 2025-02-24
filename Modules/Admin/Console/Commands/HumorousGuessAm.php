<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Services\picture\PictureService;
use Symfony\Component\Console\Input\InputOption;

class HumorousGuessAm extends Command
{
    protected $_history = false; //

    protected $_years = [2023];

    protected $_lotteryTypes = [6]; // [5, 6]

    // 快乐8幽默竞猜 调用 6c
    protected $_period_list_url = 'https://api.6ctkapi.com/api/article_index?type_id=4&page=1&periods=%d';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:humorous-guess-am';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取历史幽默竞猜数据支持每日更新数据.';

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
     * PHP-FPM版
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        set_time_limit(0);
        foreach ($this->_lotteryTypes as $lotteryType) {
            if ($this->_history) {
                foreach ($this->_years as $year) {
                    $this->do($lotteryType, $year);
                }
            } else {
                // todo 只拉取最新一期的猜测
                $this->do($lotteryType, date('Y'));
            }
        }

    }

    public function do($lotteryType, $year)
    {
        $response = Http::withOptions([
            'verify'=>false
        ])->withHeaders([
            'lotteryType' => $lotteryType,
            'User-Agent' => 'Chrome/49.0.2587.3',
            'Accept' => '*',
        ])->get($this->_period_list_url);
        if($response->status() != 200) {
//            Log::error('命令【module:humorous-guess】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
            return ;
        }
        $res = json_decode($response->body(), true);

        if($res['code'] == 200 && !empty($res['data'])) {
            $guessList['guessId']           = 0;
            $guessList['year']              = $year;
            $guessList['lotteryType']       = $lotteryType;
            $guessList['issue']             = $res['data']['periods'];
            $guessList['title']             = $year.'年'.$res['data']['periods'].'期';
            $guessList['pictureTitle']      = $res['data']['title'] ?? '';
            $attr = $this->imgStr($res['data']['content']);
            $guessList['pictureContent']    = $attr['imageContent'];
            $guessList['imageUrl']          = $attr['src'];
            $imageAttr = (new PictureService())->getImageInfo($attr['src']);
            $guessList['width']             = $imageAttr['width'] ?? 0;
            $guessList['height']            = $imageAttr['height'] ?? 0;
            $guessList['videoTitle']        = '';
            $guessList['videoContent']      = $res['data']['video_content'] ?? '';
            $guessList['videoUrl']          = $res['data']['video_url'] ?? '';
            $this->db($guessList);
        } else {
            return false;
        }
    }

    /**
     * 历史数据
     * @param $lotteryType
     * @param $year
     * @return false|void
     */
    public function do1($lotteryType, $year)
    {
        try {
            for($i=155; $i<=203; $i++) {
                $response = Http::withOptions([
                    'verify'=>false
                ])->withHeaders([
                    'lotteryType' => $lotteryType,
                    'User-Agent' => 'Chrome/49.0.2587.3',
                    'Accept' => '*',
                ])->get(sprintf($this->_period_list_url, $i));
                if($response->status() != 200) {
                    Log::error('命令【module:humorous-guess】，出现非200状态码，请立即排查。当前状态码：'.$response->status());
                    exit('终止此次');
                }
                $res = json_decode($response->body(), true);

                if($res['code'] == 200 && !empty($res['data'])) {
                    $guessList[$i]['guessId']           = 0;
                    $guessList[$i]['year']              = $year;
                    $guessList[$i]['lotteryType']       = $lotteryType;
                    $guessList[$i]['issue']             = $res['data']['periods'];
                    $guessList[$i]['title']             = $year.'年'.$res['data']['periods'].'期';
                    $guessList[$i]['pictureTitle']      = $res['data']['title'] ?? '';
                    $attr = $this->imgStr($res['data']['content']);
                    $guessList[$i]['pictureContent']    = $attr['imageContent'];
                    $guessList[$i]['imageUrl']          = $attr['src'];
                    $imageAttr = (new PictureService())->getImageInfo($attr['src']);
                    $guessList[$i]['width']             = $imageAttr['width'] ?? 0;
                    $guessList[$i]['height']            = $imageAttr['height'] ?? 0;
                    $guessList[$i]['videoTitle']        = '';
                    $guessList[$i]['videoContent']      = $res['data']['video_content'] ?? '';
                    $guessList[$i]['videoUrl']          = $res['data']['video_url'] ?? '';
                } else {
                    return false;
                }
            }
            $this->db($guessList);
        }catch (\Exception $exception) {
//            dd($exception->getMessage(), $exception->getLine());
        }
    }

    public function db($guessList)
    {
        DB::table('humorous')
            ->upsert($guessList, ['year', 'lotteryType', 'issue']);
    }

    public function imgStr($content)
    {
        // 使用正则表达式提取第一张img标签和内容
        $pattern = '/<img[^>]+>/'; // 匹配img标签
        preg_match($pattern, $content, $matches);

        // $matches[0] 将包含第一张匹配到的img标签和内容
        $extracted_img_tag = $matches[0];

        // 使用正则表达式删除第一张img标签和内容
        $processed_text = preg_replace($pattern, '', $content, 1);

        // 使用正则表达式提取img标签中的src属性值
        $src_pattern = '/src=[\'"]([^\'"]+)[\'"]/'; // 匹配src属性值
        preg_match($src_pattern, $extracted_img_tag, $src_matches);

        // $src_matches[1] 将包含提取到的src属性值
        $src_attribute_value = $src_matches[1];

        return ['image'=>$extracted_img_tag, 'imageContent'=>$processed_text, 'src'=>$src_attribute_value];
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
