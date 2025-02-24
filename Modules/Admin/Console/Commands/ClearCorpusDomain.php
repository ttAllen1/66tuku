<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearCorpusDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:clearCorpusDomain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清除资料里的域名.';

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
        try {
            $searchString = '552464';
            for ($i = 1; $i <= 30; $i++) {
                echo '处理资料表: ' . $i . PHP_EOL;
                $data = DB::table('corpus_articles' . $i)->where('content', 'like', '%' . $searchString . '%')->get();
                foreach ($data as $item) {
                    echo '处理资料ID: ' . $item->id . PHP_EOL;
                    $content = str_replace($searchString, '', $item->content);
                    DB::table('corpus_articles' . $i)->where('id', $item->id)->update(['content' => $content]);
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            sleep(30);
        }
    }

}
