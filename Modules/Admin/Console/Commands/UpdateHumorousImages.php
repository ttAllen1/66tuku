<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class UpdateHumorousImages extends Command
{

    protected $signature = 'module:update-humorous-images';

    protected $description = '将相同的keyword删除';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        return ;
        $url = 'https://amo.yaoan-learn.com:4949/col/';
        $name = 'ymktcc.jpg';
        $year = date('Y');
        $imgPrefix = config('config.full_srv_img_prefix');
        for($i=1;$i<=115;$i++) {
            // 拼接图片地址
            $img_url = $url.$i.'/'.$name;
            // 获取图片内容
            $img_content = file_get_contents($img_url);
//            dd($img_content);
            // 保存图片
            $filePath = 'upload/humorous/'.$year. '/xinao/' .$i.'/ymktcc.jpg';
            $path = public_path($filePath);
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($path, $img_content);
            $this->info('第'.$i.'张图片下载完成');
            DB::table('humorous')
                ->where('year', $year)
                ->where('lotteryType', 2)
                ->where('issue', $i)
                ->update(['imageUrl'=>$imgPrefix . $filePath]);
            $this->info('第'.$i.'张图片地址更新完成');
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
