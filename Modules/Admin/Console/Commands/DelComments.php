<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\CorpusType;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\User;
use Modules\Api\Models\UserComment;
use Modules\Common\Services\BaseService;
use Swoole\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DelComments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:del-comments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除假评论';

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
        $userIds = User::query()->where('is_chat', 1)->pluck('id')->toArray();
        while (true) {
            try{
                ini_set('memory_limit', '-1');
                UserComment::query()
                    ->select(['id'])
                    ->whereIn('user_id', $userIds) // 筛选用户 ID 在 userIds 列表中的评论
                    ->whereDate('created_at', '<=', now()->subDays(5)->format('Y-m-d')) // 筛选评论创建日期在指定范围内的评论
                    ->limit(10000)
                    ->delete();
                sleep(1);
//                $monthsToDelete = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10]; // 定义每个进程要删除的月份
//                foreach ($monthsToDelete as $k =>  $monthsAgo) {
//                    $process = new Process(function() use ($userIds, $monthsAgo, $k) {
//                        $startDate = Carbon::now()->subMonths($monthsAgo + 1)->format('Y-m-d');
//                        $endDate = Carbon::now()->subMonths($monthsAgo)->format('Y-m-d');
//                        $batchSize = 1000;
//                        // 每次批量删除的大小
//                        UserComment::query()
//                            ->select(['id'])
//                            ->whereIn('user_id', $userIds) // 筛选用户 ID 在 userIds 列表中的评论
//                            ->whereBetween('created_at', [$startDate, $endDate]) // 筛选评论创建日期在指定范围内的评论
//                            ->chunkById($batchSize, function ($comments, $k) { // 每次处理 $batchSize 条评论
//                                $commentIds = $comments->pluck('id')->toArray(); // 获取当前批次评论的 ID 列表
//                                UserComment::query()->whereIn('id', $commentIds)->delete(); // 删除当前批次的评论
//                                echo "进程".$k."删除了 " . count($commentIds) . " 条评论..." . PHP_EOL; // 输出删除数量
//                            });
//
//                    });
//                    $process->start();
//                }

//                Process::wait();
            }catch (\Exception $exception) {
                continue;
            }
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
