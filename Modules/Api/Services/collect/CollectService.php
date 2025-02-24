<?php

namespace Modules\Api\Services\collect;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class CollectService  extends BaseApiService
{
    private $_columns = [];
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 收藏 | 取消收藏
     * @param Builder $model
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect(Builder $model): JsonResponse
    {
        $table = $model->from;
        $this->_columns = Schema::getColumnListing($table);
        $userId = auth('user')->id();
        if ( $this->checkUserCollect($model, $userId) ) {
            if (in_array('collectCount', $this->_columns)) {
                $model->decrement('collectCount');
            }
            $model->first()->collect()->where('user_id', $userId)->delete();

            return $this->apiSuccess(ApiMsgData::COLLECT_API_CANCEL);
        } else {
            if (in_array('collectCount', $this->_columns)) {
                $model->increment('collectCount');
            }
            $model->first()->collect()->create([
                'user_id'   => $userId,
            ]);
            return $this->apiSuccess(ApiMsgData::COLLECT_API_SUCCESS);
        }
    }

    /**
     * 判断用户是否对指定模型收过藏
     * @param $model
     * @param $userId
     * @return bool
     * @throws CustomException
     */
    private function checkUserCollect($model, $userId) :bool
    {
        try{
            $collect = $model->with(['collect'=>function($query) use ($userId) {
                $query->where('user_id', $userId);
            }])->first(['id']);
            if (!$collect) {
                throw new CustomException(['message'=>'目标数据不存在']);
            }
        }catch (RelationNotFoundException $exception) {
            Log::channel('_db')->error('目标模型collect关联不存在', ['message'=>get_class($model).' 不存在`collect`关联']);
            throw new CustomException(['message'=>'目标模型collect不存在']);
        }
        if($collect['collect']) {
            return true;
        }
        return false;
    }

}
