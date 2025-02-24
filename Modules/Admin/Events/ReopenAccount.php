<?php

namespace Modules\Admin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReopenAccount
{
    use SerializesModels, Dispatchable;

    public $userBets;
    public $mysqlIssue;
    public $year;
    public $lotteryType;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($userBets, $mysqlIssue, $year, $lotteryType)
    {
        $this->userBets= $userBets;
        $this->mysqlIssue= $mysqlIssue;
        $this->year= $year;
        $this->lotteryType= $lotteryType;
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
