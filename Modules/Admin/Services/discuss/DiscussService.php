<?php
/**
 * @Name 高手论坛管理服务
 * @Description
 */

namespace Modules\Admin\Services\discuss;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\CorpusType;
use Modules\Api\Models\Discuss;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class DiscussService extends BaseApiService
{
    /**
     * @name 论坛列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        $list = Discuss::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['is_top'] != -1, function ($query) use ($data) {
                $query->where('is_top', $data['is_top']);
            })
            ->when($data['is_essence'] != -1, function ($query) use ($data) {
                $query->where('is_essence', $data['is_essence']);
            })
            ->when($data['lotteryType'] != 0, function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->when($data['title'], function ($query) use ($data) {
                $query->where('title', 'like', '%'.$data['title'].'%');
            })
            ->when($data['content'], function ($query) use ($data) {
                $query->where('content', 'like', '%'.$data['content'].'%');
            })
            ->when($data['nickname'], function($query) use ($data) {
                $query->whereHas('user', function($query) use ($data) {
                    $query->when($data['nickname'], function($query) use ($data) {
                            $query->where('nickname', 'like', '%'.$data['nickname'].'%');
                        });
                });
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'nickname']);
            }, 'images'])
            ->latest()
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total'],
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id,array $params){
        $discuss = Discuss::query()->where('id', $id)->first();
        if (isset($params['imageUrl'])) {
            $discuss->images->each->delete();
            $this->attachImage($params['imageUrl'], $discuss);
            unset($params['imageUrl']);
        }

        unset($params['images']);
        $params['content'] = str_replace(PHP_EOL, '', $params['content']);

        return $this->commonUpdate(Discuss::query(),$id,$params);
    }

    public function status($id,$params)
    {
        $id = is_array($id) ? $id : [$id];

        return $this->commonUpdate(Discuss::query(),$id,$params);
    }

    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     **/
    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        // 查询出所有帖子的用户ID
        $userIds = DB::table('discusses')->whereIn('id', $id)->pluck('user_id')->toArray();
        // 同时删除图片表和图片资源
        foreach ($id as $v) {
            Discuss::query()->find($v)->images->each->delete();
            // 删除评论
            Discuss::query()->find($v)->comments()->delete();
        }
        foreach ($userIds as $k => $v) {
            DB::table('users')->where('id', $v)->where('releases', '>', 0)->decrement('releases');
        }

        return $this->commonDestroy(Discuss::query(),$id);
    }

    public function store(array $params)
    {
        $nextIssue = (int)$this->getNextIssue($params['lotteryType']);
        try{
            $userId = $params['user_id'];
            $discuss = Discuss::query()->create([
                'user_id'       => $userId,
                'lotteryType'   => $params['lotteryType'],
                'title'         => $params['title'],
                'content'       => str_replace(PHP_EOL, '', $params['content']),
                'word_color'    => '',
                'issue'         => $nextIssue,
                'year'          => date('Y'),
                'thumbUpCount'  => $params['thumbUpCount'],
                'views'         => $params['views'],
                'is_essence'    => $params['is_essence'],
                'is_top'        => $params['is_top'],
                'commentCount'  => $params['commentCount'],
                'status'        => $params['status']
            ]);
            DB::table('users')->where('id', $userId)->increment('releases');
            if (isset($params['imageUrl'])) {
                $this->attachImage($params['imageUrl'], $discuss);
            }
        }catch (\Exception $exception) {
            return $this->apiError(ApiMsgData::ADD_API_ERROR);
        }

        return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);
    }

    public function previous($params): \Illuminate\Http\JsonResponse
    {
        $discuss = Discuss::query()->where('user_id', $params['user_id'])
            ->where('lotteryType', $params['lotteryType'])
            ->where('status', 1)
            ->select(['id', 'title', 'content', 'thumbUpCount', 'commentCount', 'views', 'is_essence', 'is_top', 'status'])
            ->latest()
            ->with(['images'])
            ->first();
        if (!$discuss) {
            throw new CustomException(['message'=>'数据不存在']);
        }

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $discuss->toArray());
    }

    public function list($params)
    {
        $list = CorpusType::query()
            ->orderBy('lotteryType')
            ->orderBy('id')
            ->when($params['lotteryType']!=0, function($query) use ($params) {
                $query->where('lotteryType', $params['lotteryType']);
            })
            ->where('website', 2)
            ->when($params['corpusTypeName'], function($query) use ($params) {
                $query->where('corpusTypeName', 'like', '%'.$params['corpusTypeName'].'%');
            })
            ->paginate($params['limit'])->toArray();
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['is_index'] = (bool)$v['is_index'];
        }
        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total'],
        ]);
    }

    public function update_is_index($id,$params)
    {
        $params['is_index'] = $params['is_index'] ? 1 : 0;
        return $this->commonUpdate(CorpusType::query(),$id,$params);
    }


}
