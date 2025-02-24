<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\FiveBliss;

class FiveVirtualNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:fiveVirtual';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '五福增加虚拟人数.';

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
        while (true) {
            $aac = AuthActivityConfig::val('five_bliss_open,five_bliss_start,five_bliss_end,five_bliss_show_end');
            if (strtotime($aac['five_bliss_show_end']) < time() || !$aac['five_bliss_open']) {
                DB::table('auth_activity_configs')->where('k', 'five_bliss_count')->update(['v' => 0]);
            }
            $fiveBliss = FiveBliss::get();
            $max = 0;
            foreach ($fiveBliss as $item) {
                list($number, $unit) = mb_str_split($item->condition);
                if ($unit == '天' && $number > $max) {
                    $max = $number;
                }
            }
            $startBlissTime = strtotime(date('Y-m-d', strtotime($aac['five_bliss_start']) + 60 * 60 * 24 * ($max-1)));
            if ($startBlissTime <= time() && strtotime($aac['five_bliss_end']) > time()) {
                if (rand(0, 1) == 1) {
                    DB::table('auth_activity_configs')->where('k', 'five_bliss_count')->increment('v', rand(0, 34));
                }
            }
            sleep(31);
        }
    }

}
