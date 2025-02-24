<?php

namespace Modules\Admin\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Modules\Admin\Console\Commands\HumorousGuess;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\Spider::class,
        Commands\SpiderNumbers::class,
    ];


    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
