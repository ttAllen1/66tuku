<?php

namespace Modules\Admin\Listeners;

use Modules\Admin\Events\TestEvent1;

class TestListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param TestEvent1 $event
     * @return void
     */
    public function handle(TestEvent1 $event)
    {
        echo 'TestListener（TestEvent1）:'.$event->a.PHP_EOL;
    }
}
