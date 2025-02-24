<?php
/**
 * @Name 会员分组管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Modules\Admin\Models\Group;
use Modules\Admin\Models\User;
use Modules\Admin\Services\BaseApiService;

class UserGroupService extends BaseApiService
{


    /**
     * @name 会员列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
        // 获取所有组别
        $list = Group::query()->paginate($data['limit'])->toArray();

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total']
        ]);
    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        $data['password'] = bcrypt($data['password']);
        $data['register_at'] = date('Y-m-d H:i:s');


        if (empty($data['remark'])) {
            unset($data['remark']);
        }
        // 生成唯一邀请码
        $data['invite_code'] = $this->getUserInviteCode();
        return $this->commonCreate(User::query(),$data);
    }

    /**
     * @name 修改页面
     * @description
     * @param  id Int 管理员id
     * @return JSON
     **/
    public function edit(int $id){

    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     **/
    public function update(int $id,array $data){
        // 用户组别中间表
        if ( isset($data['group_id']) && !empty($data['group_id']) ) {
            User::query()->find($data['id'])->groups()->sync($data['group_id']);
        }
        unset($data['group_id']);
        return $this->commonUpdate(User::query(),$id,$data);
    }
    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     **/
    public function status(int $id,array $data){
        return $this->commonStatusUpdate(User::query(),$id,$data);
    }

}
