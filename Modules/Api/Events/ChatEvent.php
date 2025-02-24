<?php

namespace Modules\Api\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Api\Models\UserChat;

class ChatEvent
{
    public $sendMsg;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($sendMsg)
    {
        $this->sendMsg = $sendMsg;
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
