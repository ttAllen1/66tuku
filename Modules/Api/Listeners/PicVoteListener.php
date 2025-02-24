<?php

namespace Modules\Api\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Api\Events\CreateVoteByPic;
use Modules\Api\Models\Vote;
use Modules\Api\Services\vote\VoteService;

class PicVoteListener implements ShouldQueue
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

//    public $queue = '{queue-vote}';

    /**
     * Handle the event.
     * @param CreateVoteByPic $event
     * @return void
     */
    public function handle(CreateVoteByPic $event)
    {
        try{
            $zodiac = ["牛", "马", "羊", "鸡", "狗", "猪", "鼠", "虎", "兔", "龙", "蛇", "猴"];
            $res = (new VoteService())->insertUserVote($event->_pic_detail, Vote::$_vote_zodiac[$zodiac[array_rand($zodiac)]], 54377);
//            dd($res);
        } catch (\Exception $exception) {
            return;
        }
    }
}
