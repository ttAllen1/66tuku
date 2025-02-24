<?php

namespace Modules\Admin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Admin\Models\AuthConfig;

class HkLive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'module:hklive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '香港直播链接检测是否更换.';

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
            while (true) {
                $data = Http::get('https://tv.on.cc/js/live/live_feed_v3.js');
                if ($data->status() == 200) {
                    foreach ($data->json() as $item) {
                        $title = $item['camera'][0]['title'][0];
                        if (preg_match("/六合彩/i", $title)) {
                            $xgLiveUrl = $item['camera'][0]['signal_key'][0];
                            $xgLiveUrlOld = AuthConfig::value('xg_live');
                            $liveFirst = substr($xgLiveUrl, 0, strrpos($xgLiveUrl, '/'));
                            $liveFirstOld = substr($xgLiveUrlOld, 0, strrpos($xgLiveUrlOld, '/'));
                            if ($liveFirstOld != $liveFirst) {
                                $newLiveUrl = str_replace($liveFirstOld, $liveFirst, $xgLiveUrlOld);
                                AuthConfig::query()->where('id', 1)->update(['xg_live' => $newLiveUrl]);
                            }
                        }
                    }
                }
                echo 'sleep 30' . PHP_EOL;
                sleep(30);
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            sleep(30);
        }
    }

}
