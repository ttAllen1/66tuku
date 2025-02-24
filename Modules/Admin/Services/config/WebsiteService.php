<?php

/**
 * 三方站点服务
 * @Description
 */

namespace Modules\Admin\Services\config;

use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\User;
use Modules\Admin\Models\WebConfig;
use Modules\Admin\Services\BaseApiService;

class WebsiteService extends BaseApiService
{
    /**
     * @name 配置页面
     * @description
     **/
    public function index(array $data){
        $list = WebConfig::query()
            ->when($data['web_name'], function ($query) use ($data){
                $query->where('web_name', 'like', '%'.$data['web_name'].'%');
            })
            ->when($data['status'] != 0, function ($query) use ($data){
                $query->where('status', $data['status']);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total']
        ]);
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {
        $data['token'] = md5($data['web_url'].md5('fr76Gw(82&&8wdsWBbpGQW'));

        return $this->commonCreate(WebConfig::query(),$data);
    }

    /**
     * @param $id
     * @param array $data
     * @return JsonResponse|null
     */
    public function update($id, array $data): ?JsonResponse
    {
        // 判断图像是否更改
//        dd($id, $data);
        $old_avatar_prefix_url = WebConfig::query()->where('id', $id)->value('avatar_prefix_url');
        if ($old_avatar_prefix_url == $data['avatar_prefix_url']) {
            return $this->commonUpdate(WebConfig::query(),$id,$data);
        }
        // 更新用户表图像信息
        $userList = User::query()->where('web_id', $id)->select(['id', 'avatar'])->get()->toArray();
        if (!$userList) {
            return $this->commonUpdate(WebConfig::query(),$id,$data);
        }
        foreach ($userList as $k => $v) {
            $userList[$k]['avatar'] = str_replace($old_avatar_prefix_url, $data['avatar_prefix_url'], $v['avatar']);
        }
        \Mavinoo\Batch\BatchFacade::update(new User, $userList, 'id');
        return $this->commonUpdate(WebConfig::query(),$id,$data);
    }

    public function status($id, array $data): ?JsonResponse
    {
        return $this->commonUpdate(WebConfig::query(),$id,$data);
    }

    public function delete($id){
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(WebConfig::query(),$id);
    }
}
