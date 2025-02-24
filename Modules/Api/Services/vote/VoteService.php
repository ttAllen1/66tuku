<?php

namespace Modules\Api\Services\vote;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Api\Models\Vote;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\CustomException;

class VoteService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 根据 Model $model 实现用户投票
     * @param Model $model
     * @param $vote_zodiac
     * @param int $isAdminUserId
     * @return mixed
     * @throws CustomException
     */
    public function insertUserVote(Model $model, $vote_zodiac, int $isAdminUserId=0)
    {
        if (!$model->id) {
            Log::channel('_db')->error(__CLASS__.'['.__FUNCTION__.']', ['message=>模型ID不存在']);
            throw new CustomException(['message'=>'请稍后重试']);
        }
        if($isAdminUserId){
            $user_id = $isAdminUserId;
        } else {
            $user_id = auth('user')->id();
        }

        $picVoteModel = $model->votes;
        $userVoteService = new UserVoteService();
        if (!$isAdminUserId) {
            if ($picVoteModel && $userVoteService->hasUserVote($user_id, $picVoteModel->id)) {
                throw new CustomException(['message'=>'您已经投票了']);
            }
        }

        try{
            // 1. 判断该对象是否是第一次被投票
            if (!$picVoteModel) {
                $picVoteModel = $model->votes()->create(Vote::userVoteInitData($vote_zodiac));
            } else {
                $picVoteArray = $picVoteModel->toArray();
                $sx = Vote::$_sx;
                $picVoteArray['total_num'] += 1;
                for ($i=1; $i<=12; $i++) {
                    if ($sx[$i] == $vote_zodiac) {
                        $picVoteArray[$sx[$i]]['vote_num'] += 1;
                    }
                    $picVoteArray[$sx[$i]]['percentage'] = bcmul(bcdiv($picVoteArray[$sx[$i]]['vote_num'], $picVoteArray['total_num'], 4), 100, 2);
                }

                $model->votes()->update($picVoteArray);
            }
        }catch (\Exception $exception) {
            Log::channel('_db')->error(__CLASS__.'['.__FUNCTION__.']', ['message=>'.$exception->getMessage()]);
            throw new CustomException(['message'=>'请稍后重试']);
        }
        if ($picVoteModel) {
            $userVoteService->userVote($user_id, $picVoteModel->id);
        }
    }

    /**
     * 获取投票信息   【图片 ｜ 幽默竞猜】
     * @param Model $model
     * @return array
     */
    public static function getVoteData(Model $model): array
    {
        $list = $model->votes;
        if ( !$list ) {
            $obj = Vote::$_sx_init_obj;
            unset($obj['total_num']);
            // 返回初始化投票值
            sort($obj);
            return $obj;
        }
        $data = [];
        $list1 = $list->toArray();
        foreach ($list1 as $k => $v) {
            if (in_array($k, Vote::$_sx)) {
                $data[$k] = $v;
            }
        }

        return array_values($data);
    }

}
