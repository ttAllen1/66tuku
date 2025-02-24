<?php
/**
 * @Name 会员分组管理服务
 * @Description
 */

namespace Modules\Admin\Services\discover;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\AuthConfig;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\UserDiscovery;
use Modules\Common\Exceptions\ApiMsgData;

class DiscoverService extends BaseApiService
{
    /**
     * 发现列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function discover_list(array $data)
    {
        $list = UserDiscovery::query()
            ->where('type', $data['type'])
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['lotteryType'] != 0, function ($query) use ($data) {
                $query->where('lotteryType', $data['lotteryType']);
            })
            ->when($data['title'], function ($query) use ($data) {
                $query->where('title', 'like', '%'.$data['title'].'%');
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
        $video_url = AuthConfig::query()->where('id', 1)->value('video_url');
        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total'],
            'video_url' => $video_url
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function discover_update_status($id,array $data){
        return $this->commonUpdate(UserDiscovery::query(),$id,$data);
    }

    // 删除
    public function discover_update($id,array $params){
        $discover = UserDiscovery::query()->where('id', $id)->first();
        if ($params['type'] == 1 && isset($params['imageUrl'])) {
            $discover->images->each->delete();
            $this->attachImage($params['imageUrl'], $discover);
        }
        if ($params['type'] == 2 && isset($params['videoUrl'])) {
            $discover->images->each->delete();
            $video_path = $this->attachS3Video($params['videoUrl'], $discover);
            $params['videos'] = $video_path;
        }

        unset($params['videoUrl']);
        unset($params['imageUrl']);
        unset($params['images']);
        return $this->commonUpdate(UserDiscovery::query(),$id,$params);
    }

    public function discover_delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        $userIds = DB::table('user_discoveries')->whereIn('id', $id)->pluck('user_id')->toArray();
        // 同时删除图片表和图片资源
        foreach ($id as $v) {
            UserDiscovery::query()->find($v)->images->each->delete();
        }
        foreach ($userIds as $k => $v) {
            DB::table('users')->where('id', $v)->where('releases', '>', 0)->decrement('releases');
        }
        return $this->commonDestroy(UserDiscovery::query(),$id);
    }

    public function discover_create($params){
        $nextIssue = (int)$this->getNextIssue($params['lotteryType']);
        try{
            $userId = $params['user_id'];
            $discover = UserDiscovery::query()->create([
                'user_id'       => $userId,
                'lotteryType'   => $params['lotteryType'],
                'title'         => $params['title'],
                'content'       => $params['content'],
                'type'          => $params['type'],
                'issue'         => $nextIssue,
                'year'          => date('Y'),
                'thumbUpCount'  => $params['thumbUpCount'],
                'views'         => $params['views'],
                'commentCount'  => $params['commentCount'],
                'forwardCount'  => $params['forwardCount'],
                'collectCount'  => $params['collectCount'],
                'status'        => $params['status']
            ]);
            if (!empty($params['imageUrl']) && $params['type']==1) {
                $this->attachImage($params['imageUrl'], $discover, true);
            }
            if (!empty($params['videos']) && $params['type']==2) {
                $video_path = $this->attachS3Video($params['videos'], $discover);
                $discover->update(['videos'=>$video_path]);
            }
            DB::table('users')->where('id', $userId)->increment('releases');
        }catch (\Exception $exception) {
            return $this->apiError(ApiMsgData::ADD_API_ERROR);
        }

        return $this->apiSuccess(ApiMsgData::PUBLISH_API_SUCCESS);
    }
}
