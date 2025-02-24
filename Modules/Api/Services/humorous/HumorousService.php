<?php

namespace Modules\Api\Services\humorous;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Modules\Api\Models\Humorous;
use Modules\Api\Models\Vote;
use Modules\Api\Services\ad\AdService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\collect\CollectService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\vote\VoteService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class HumorousService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取竞猜期数列表
     * @param $params
     * @return JsonResponse
     */
    public function guess($params): JsonResponse
    {
        $nextIssue = $this->getNextIssue($params['lotteryType']) ?? 0;
        $humorous = Humorous::query()
            ->where('year', $params['year'])
            ->where('lotteryType', $params['lotteryType'])
            ->when($params['year'] == date('Y' && $params['year']!=2024) && $nextIssue, function($query) use ($nextIssue) {
                $query->where('issue', '<=', $nextIssue);
            })
            ->orderBy('issue', 'desc')
            ->get(['id', 'guessId', 'issue']);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $humorous->toArray());
    }

    /**
     * 获取竞猜详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail($params): JsonResponse
    {
        $humorous = Humorous::query()
            ->where('id', $params['id'])->first();
        if (!$humorous) {
            throw new CustomException(['message'=>'数据不存在']);
        }
        // 获取投票信息
        $vote = VoteService::getVoteData($humorous);
        // 获取评论信息
//        $comments = $humorous->comments()->where('status', 1)->select(['id', 'user_id', 'content', 'is_hot', 'thumbUpCount', 'subCommentCount', 'level', 'created_at'])->with(['user'=>function($query){
//            $query->select(['id', 'name', 'nickname', 'account_name', 'avatar']);
//        }])->orderBy('created_at', 'desc')
//            ->simplePaginate()->toArray();
        unset($humorous['votes']);

        $humorous['follow'] = false;
        $humorous['collect'] = false;
        $userId = auth('user')->id();
        if ($userId) {
            $humorous['follow']  = (bool)$humorous->follow()->where('user_id', $userId)->value('id');
            $humorous['collect'] = (bool)$humorous->collect()->where('user_id', $userId)->value('id');
        }
        if ($humorous['clickCount']==0) {
            $humorous->increment('clickCount', $this->getFirstViews());
        } else {
            $humorous->increment('clickCount', $this->getSecondViews());
        }
        if ($humorous['imageUrl']) {
            $humorous['imageUrl'] = $this->transImgUrl($humorous['lotteryType'], $humorous['year'], $humorous['imageUrl']);
        }
        if ($humorous['videoUrl']) {
            $humorous['videoUrl'] = $this->transVideoUrl($humorous['lotteryType'], $humorous['year'], $humorous['videoUrl']);
        }
        $obj['HumorousDetailData'] = $humorous->toArray();
        $obj['voteList'] = $vote;

        // 详情广告
//        $adList = (new AdService())->getAdListByPoi([2]);
//        $obj['adList'] = $adList;

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $obj);
    }

    /**
     * 图解点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        $humorousBuilder = Humorous::query()->where('id', $params['id']);

        return (new FollowService())->follow($humorousBuilder);
    }

    /**
     * 图解收藏
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect($params): JsonResponse
    {
        $humorousBuilder = Humorous::query()->where('id', $params['id']);

        return (new CollectService())->collect($humorousBuilder);
    }

    /**
     * 图解投票
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function votes($params): JsonResponse
    {
        try{
            $humorousModel = Humorous::query()->where('id', $params['id'])->firstOrFail(['id']);
            (new VoteService())->insertUserVote($humorousModel, Vote::$_vote_zodiac[$params['vote_zodiac']]);
        }catch (ModelNotFoundException $exception) {
            throw new CustomException(['message'=>'pictureId不存在']);
        }
        $vote = VoteService::getVoteData(Humorous::query()->where('id', $params['id'])->firstOrFail(['id']));

        return $this->apiSuccess(ApiMsgData::VOTE_API_SUCCESS, ['voteList'=>$vote]);
    }

    /**
     * 临时替换幽默竞猜图片地址
     * @param $lotteryType
     * @param $year
     * @param $imageUrl
     * @return array|string|string[]
     */
    private function transImgUrl($lotteryType, $year, $imageUrl)
    {
//        dd($imageUrl);
        if ($lotteryType == 1 ) {
            return str_replace(['https://tk.ku33a.net:4949', 'https://tk.zaojiao365.net:4949', 'https://tk.ku33a.net:4949', 'https://tk.cgpoweredu.net:4949', 'https://tk.moshoushijie.net:4949', 'https://tk2.baegg.com:4949'], 'https://tk.wyvogue.com:4949', $imageUrl);
        }
        if ($lotteryType == 2 ) {
            return str_replace(['https://tk2.zaojiao365.net:4949', 'https://tk2.ku33a.net:4949', 'https://tk2.cgpoweredu.net:4949', 'https://tk2.moshoushijie.net:4949'], 'https://tk2.baegg.com:4949', $imageUrl);
        }
        if ($lotteryType == 3 ) {
            return str_replace(['https://tk3.zaojiao365.net:4949', 'https://tk3.ku33a.net:4949', 'https://tk3.cgpoweredu.net:4949'], 'https://tk3.moshoushijie.net:4949', $imageUrl);
        }
        if ($lotteryType == 4 ) {
            return str_replace(['https://tk5.zaojiao365.net:4949', 'https://tk5.ku33a.net:4949', 'https://tk5.cgpoweredu.net:4949'], 'https://tk5.moshoushijie.net:4949', $imageUrl);
        }
        if (Str::startsWith($imageUrl, 'http')) {
            return $imageUrl;
        } else {
            $prefix = $this->getImgPrefix()[$year][$lotteryType];
            if (is_array($prefix)) {
                return $prefix[array_rand($prefix)].$imageUrl;
            }
            return $this->getImgPrefix()[$year][$lotteryType].$imageUrl;
        }
//        dd($this->getImgPrefix());
        if ($lotteryType == 1) { // getPicUrl
            if (Str::startsWith($imageUrl, 'http')) {
                return $imageUrl;
            } else {
                return $this->getImgPrefix()[$year][$lotteryType].$imageUrl;
            }

        } else if ($lotteryType == 2) {
            return str_replace('https://tk2.jixingkaisuo.com:4949/', $this->_pic_base_url[$year][$lotteryType], $imageUrl);
        } else if ($lotteryType == 3) {
            return str_replace('https://tk3.jixingkaisuo.com:4949/', $this->_pic_base_url[$year][$lotteryType], $imageUrl);
        } else if ($lotteryType == 4) {
            return str_replace('https://tk5.jixingkaisuo.com:4949/', $this->_pic_base_url[$year][$lotteryType], $imageUrl);
        }

        return $imageUrl;
    }

    /**
     * 临时替换幽默竞猜图片地址
     * @param $lotteryType
     * @param $year
     * @param $imageUrl
     * @return array|string|string[]
     */
    private function transVideoUrl($lotteryType, $year, $imageUrl)
    {
        if ($lotteryType == 1 ) {
            return str_replace(['https://sp.ku33a.net:4949', 'https://sp.zaojiao365.net:4949', 'https://sp.cgpoweredu.net:4949', 'https://sp.moshoushijie.net:4949', 'https://sp.baegg.com:4949'], 'https://sp.wyvogue.com:4949', $imageUrl);
        }
        if ($lotteryType == 2 ) {
            return str_replace(['https://sp.zaojiao365.net:4949', 'https://sp.ku33a.net:4949', 'https://sp.cgpoweredu.net:4949', 'https://sp.moshoushijie.net:4949'], 'https://sp.baegg.com:4949', $imageUrl);
        }
        if ($lotteryType == 3 ) {
            return str_replace(['https://sp.zaojiao365.net:4949', 'https://sp.ku33a.net:4949', 'https://sp.cgpoweredu.net:4949'], 'https://sp.moshoushijie.net:4949', $imageUrl);
        }
        if ($lotteryType == 4 ) {
            return str_replace(['https://sp.zaojiao365.net:4949', 'https://sp.ku33a.net:4949', 'https://sp.cgpoweredu.net:4949'], 'https://sp.moshoushijie.net:4949', $imageUrl);
        }


        return $imageUrl;
    }
}
