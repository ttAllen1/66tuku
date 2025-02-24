<?php

namespace Modules\Api\Events;

use Illuminate\Queue\SerializesModels;

class CreateVoteByPic
{
    use SerializesModels;
    public $_pic_detail;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($PicDetailData)
    {
        $this->_pic_detail = $PicDetailData;
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
