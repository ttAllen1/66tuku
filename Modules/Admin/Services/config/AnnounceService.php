<?php

/**
 * @Name 站内信服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Modules\Admin\Models\StationMsg;
use Modules\Admin\Services\BaseApiService;
use Modules\Admin\Services\user\UserMessageService;

class AnnounceService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index(array $data){
        $list = StationMsg::query()
            ->when($data['type'], function ($query) use ($data){
                $query->where('type', $data['type']);
            })
            ->when($data['status'], function ($query) use ($data){
                $query->where('status', $data['status']);
            })
            ->orderBy('created_at', 'desc')
            ->with(['user_msg.users:id,account_name'])
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        try{
            if ($data['type'] == 1 && isset($data['date_range']) && !empty($data['date_range'][0])) {
                $data['valid_date_start'] = $data['date_range'][0];
            }
            if ($data['type'] == 1 && isset($data['date_range']) && !empty($data['date_range'][1])) {
                $data['valid_date_end'] = $data['date_range'][1];
            }
            unset($data['date_range']);
            unset($data['id']);
            $orm = StationMsg::query()->create($data);

            if ($data['type'] == 2 && $data['appurtenant'] == 2 && count($data['user_id']) >0) {
                (new UserMessageService())->insertToUserMsg($data['user_id'], $orm->id);
            }
            return $this->apiSuccess('创建成功');
        }catch (\Exception $e) {
            return $this->apiError('创建失败');
        }
    }



    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id, array $data){
        try{
            if (!is_array($id)) {
                $id = [$id];
            }
//            if (isset($data['type'])) {
//                (new UserMessageService())->deleteToUserMsg($id);
//            }
            if (isset($data['type']) && $data['type'] == 1 && isset($data['date_range']) && !empty($data['date_range'][0])) {
                $data['valid_date_start'] = $data['date_range'][0];
            }
            if (isset($data['type']) && $data['type'] == 1 && isset($data['date_range']) && !empty($data['date_range'][1])) {
                $data['valid_date_end'] = $data['date_range'][1];
            }
            if (isset($data['type']) && $data['type'] == 2 && $data['appurtenant'] == 2 && count($data['user_id']) >0) {
                (new UserMessageService())->insertToUserMsg($data['user_id'], $id);
            }
            if (isset($data['type']) && $data['type'] == 2) {
                $data['valid_date_start'] = null;
                $data['valid_date_end'] = null;
            }
            unset($data['date_range']);
            unset($data['user_id']);
            $orm = StationMsg::query()->whereIn('id', $id)->update($data);
            return $this->apiSuccess('更新成功');
        }catch (\Exception $exception) {
            return $this->apiError('更新失败'.$exception->getMessage());
        }
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        (new UserMessageService())->deleteToUserMsg($id);
        return $this->commonDestroy(StationMsg::query(),$id);
    }
}
