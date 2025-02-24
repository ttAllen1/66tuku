<?php

namespace Modules\Api\Services\vote;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\UserVote;
use Modules\Api\Services\BaseApiService;

class UserVoteService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 判断用户是否已经投过票
     * @param $user_id
     * @param $voteable_id
     * @return bool
     */
    public function hasUserVote($user_id, $voteable_id): bool
    {
        $isExist = DB::table('user_votes')
            ->where('user_id', $user_id)
            ->where('vote_id', $voteable_id)
            ->value('id');

        return (bool)$isExist;
    }

    /**
     * 记录用户投票数据
     * @param $user_id
     * @param $vote_id
     * @return Builder|Model
     */
    public function userVote($user_id, $vote_id)
    {
        return UserVote::query()->create([
            'user_id'   => $user_id,
            'vote_id'   => $vote_id,
        ]);
    }
}
