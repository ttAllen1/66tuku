<?php
namespace Modules\Admin\Services\user;

use Modules\Admin\Models\User;
use Modules\Admin\Services\BaseApiService;

class UserCheckService extends BaseApiService
{
    /**
     * @name 图像列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function avatar_list(array $data)
    {
        // 获取所有组别
        $list = User::query()
            ->where('system', 0)
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('avatar_is_check', $data['status']);
            })
            ->select(['id', 'nickname', 'avatar', 'new_avatar', 'avatar_is_check'])
            ->orderBy('updated_at', 'desc')
//            ->latest()
            ->paginate($data['limit'])->toArray();
        $domain = $this->getHttp() . '/';
        return $this->apiSuccess('',[
            'list'  => $list['data'],
            'total' => $list['total'],
            'domain'  => $domain
        ]);
    }

    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update($id,array $data){
        $id = is_array($id) ? $id : [$id];
        if ($data['avatar_is_check'] == 1) {
            // 将new_avatar字段赋值给avatar
            $info = User::query()->whereIn('id', $id)->select(['id', 'avatar', 'new_avatar'])->get();
            $arr = [];
            foreach ($info as $k => $v) {
                $arr[$k]['avatar'] = $v['new_avatar'];
                $arr[$k]['avatar_is_check'] = 1;
                $arr[$k]['id'] = $v['id'];
                User::query()->where('id', $v['id'])->update([
                    'avatar'            => $v['new_avatar'],
                    'avatar_is_check'   => 1,
                    'updated_at'        => date('Y-m-d H:i:s')
                ]);
            }
            return $this->apiSuccess('审核通过');
        }
        return $this->commonUpdate(User::query(),$id,$data);
    }

}
