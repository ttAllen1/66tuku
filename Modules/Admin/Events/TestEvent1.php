<?php

namespace Modules\Admin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent1
{

    use Dispatchable, SerializesModels;
    public $a;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($a)
    {
        $this->a=$a;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return [];
    }
}
