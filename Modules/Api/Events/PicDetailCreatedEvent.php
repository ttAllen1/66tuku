<?php

namespace Modules\Api\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Api\Models\PicDetail;

class PicDetailCreatedEvent
{
    public $picDetail;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(PicDetail $picDetail)
    {
        $this->picDetail = $picDetail;
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
