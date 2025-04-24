<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\IndexPic;
use Modules\Admin\Models\YearPic;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DeleteEqKeyword extends Command
{


    protected $signature = 'module:auto-delete-eq-keyword';

    protected $description = '将相同的keyword删除';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // 第一步：构造子查询，找出重复的 keyword（出现次数 >= 2）
        $duplicateKeywords = DB::table('year_pics')
            ->select('keyword')
            ->where('year', 2025)
            ->where('lotteryType', 2)
            ->where('color', 2)
            ->whereNotNull('keyword')
            ->groupBy('keyword')
            ->havingRaw('COUNT(*) >= 2');

// 第二步：主查询，从符合条件的记录中筛选 keyword 在子查询中出现过的
        $results = YearPic::query()->where('year', 2025)
            ->where('lotteryType', 2)
            ->where('color', 2)
            ->where('is_add', 0)
            ->where('max_issue', 114)
            ->whereIn('keyword', $duplicateKeywords)
            ->select(['id', 'pictureTypeId', 'keyword'])
            ->get()->toArray();
        $yearIds = [];
        $pictureTypeIds = [];
        foreach($results as $result) {
            $yearIds[] = $result['id'];
            $pictureTypeIds[] = $result['pictureTypeId'];
        }
        DB::beginTransaction();
        YearPic::query()
            ->whereIn('id', $yearIds)
            ->where('is_add', 0)
            ->where('max_issue', 114)->update(['is_delete'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
        IndexPic::query()
            ->whereIn('pictureTypeId', $pictureTypeIds)
            ->update(['is_delete'=>1, 'updated_at'=>date('Y-m-d H:i:s')]);
        DB::commit();
        dd($results);

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
