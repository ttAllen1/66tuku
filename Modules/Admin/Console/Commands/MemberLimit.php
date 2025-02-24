<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Models\User;
use Modules\Common\Services\BaseService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MemberLimit extends Command
{
    protected $_seng_imgs = [7]; // 2, 7

    protected $token;
    protected $apiUrl;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:member-limit';       // 理论上一次性执行即可

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

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
        try{
            $date = now()->format('Y-m-d H:i:s');
            DB::beginTransaction();;
            User::query()
                ->where('is_chat', 0)
                ->where('system', 0)
                ->select('id', 'withdraw_lave_limit')
                ->chunkById(1000, function($items) use ($date) {
                    if ($items->isEmpty()) {
                        return false;
                    }
                    $items = $items->toArray();
                    $data = [];
                    foreach ($items as $k =>  $item) {
                        $data[$k]['user_id'] = $item['id'];
                        $data[$k]['plat_id'] = 0;
                        $data[$k]['quota'] = $item['withdraw_lave_limit'];
                        $data[$k]['created_at'] = $date;
                    }
                    DB::table('user_plat_quotas')->insert($data);
                });
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
            dd($exception->getMessage());
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
