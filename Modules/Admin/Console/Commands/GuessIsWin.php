<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\InputOption;

class GuessIsWin extends Command
{
    protected $_history = false;

    protected $_years = [2020, 2021, 2022, 2023];

    protected $_lotteryTypes = [1, 2, 3, 4];

    protected $_period_list_url = 'https://49208.com/unite49/h5/tool/listSinkBag?jpushId=75987&year=%d'; // 75987
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:guess-win';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '判断每个竞猜里的内容是否中奖.';

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

    }

    public function do($lotteryType, $year)
    {

    }

    public function db($data)
    {

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
