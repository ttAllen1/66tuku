<?php

namespace Modules\Admin\Events;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Api\Models\UserBet;

class OnceAccount
{
    use SerializesModels, Dispatchable;

    public $userBets;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Collection $userBets)
    {
        $this->userBets= $userBets->toArray();
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
