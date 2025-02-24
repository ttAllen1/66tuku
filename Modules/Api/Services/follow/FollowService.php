<?php

namespace Modules\Api\Services\follow;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\User;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Api\Services\user\UserService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class FollowService  extends BaseApiService
{
    private $_columns = [];
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 点赞 | 取消点赞
     * @param Builder $model
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(Builder $model): JsonResponse
    {
        $table = $model->from;
        $this->_columns = Schema::getColumnListing($table);
        $userId = auth('user')->id();
        $orm = $model->first();
        /**
         * 不存在 || 被软删除的 返回 FALSE
         * 其它 返回 TRUE
         */
        if ( $this->checkUserFollow($model, $userId) ) {
            if (in_array('thumbUpCount', $this->_columns)) {
                $model->decrement('thumbUpCount');
            }
            $orm->follow()->where('user_id', $userId)->delete(); // 实现软删除
            if ($orm->user) {
                User::query()->where('id', $orm->user->id)->decrement('likes');
            }

            return $this->apiSuccess(ApiMsgData::FOLLOW_API_CANCEL);
        } else {
            // 不存在 || 被软删除的
            if (in_array('thumbUpCount', $this->_columns)) {
                $model->increment('thumbUpCount');
            }
            // 如果存在软删除则恢复正常 不存在就新增
            $trashedModel = $orm->follow()->where('user_id', $userId)->withTrashed()->firstOrNew([
                'user_id'   => $userId
            ]);
            if ($trashedModel->trashed()) {
                // 被软删除：说明这不是第一次点赞 无需加金币等操作
                $trashedModel->restore();
            } else {
                // 不存在：这是第一次点赞 需要加金币等操作
                $trashedModel->save();
                if ($table != 'user_comments') {  // 除了评论都加金币
                    $type = 'user_activity_follow';
                    $follow_num = AuthActivityConfig::query()->where('k', 'forum_like_number')->value('v');
                    
                    (new ActivityService())->join($type, $follow_num ?? $this->_follow_num);
                }
                // 加成长值
                (new UserGrowthScoreService())->growthScore($this->_grow['follow']);
            }
            if ($orm->user) {
                User::query()->where('id', $orm->user->id)->increment('likes');
            }

            return $this->apiSuccess(ApiMsgData::FOLLOW_API_SUCCESS);
        }
    }

    /**
     * 判断用户是否对指定模型点过赞
     * @param $model
     * @param $userId
     * @return bool
     * @throws CustomException
     */
    private function checkUserFollow($model, $userId) :bool
    {
        try{
            $follow = $model->with(['follow'=>function($query) use ($userId) {
                $query->withTrashed()->where('user_id', $userId);
            }])->first(['id']);
            if (!$follow) {
                throw new CustomException(['message'=>'目标数据不存在']);
            }
        }catch (RelationNotFoundException $exception) {
            Log::channel('_db')->error('目标模型follow关联不存在', ['message'=>get_class($model).' 不存在`follow`关联']);
            throw new CustomException(['message'=>'目标模型follow不存在']);
        }
        /**
         * 不存在 || 被软删除的 返回 FALSE
         * 其它 返回 TRUE
         */
        if ($follow['follow']) {
            if ($follow['follow']->trashed()) {
                // 被软删除===> 已被删除的
                return false;
            }
            return true;
        }
        return false;
    }

}
