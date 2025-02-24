<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\PicDetail;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputOption;

class PicFav extends Command
{
    protected $_issue;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:pic-fav';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '开奖后增加收藏量.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        foreach ([1, 2, 3, 4, 6] as $v) {
            $this->_issue[$v] = (new BaseService())->getNextIssue($v);
        }
        foreach ([1, 2, 3, 4, 6] as $lotteryType ) {
            PicDetail::query()
                ->where('year', date('Y'))
                ->where('lotteryType', $lotteryType)
                ->where('issue', $this->_issue[$lotteryType])
                ->update(['collectCount'=>DB::raw('collectCount + '. rand(10, 100))]);

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
