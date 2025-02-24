<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Test extends Command
{
    static $num = 10;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:echo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        file_put_contents('1.txt', self::$num."\r", FILE_APPEND);
    }
}
