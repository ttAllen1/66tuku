<?php
/**
 * @Name 管理员修改密码服务
 * @Description
 */

namespace Modules\Admin\Services\admin;

use Modules\Admin\Models\AuthAdmin;
use Modules\Admin\Services\auth\TokenService;
use Modules\Admin\Services\BaseApiService;

class UpdatePasswordService extends BaseApiService
{
    /**
     * @name 修改密码
     * @description
     * @param  data  Array  用户数据
     * @param  data.y_password String 原密码
     * @param  data.password String 密码
     * @return JSON
     **/
    public function upadtePwdView(array $data){
        $userInfo = (new TokenService())->my();
        if (true == \Auth::guard('auth_admin')->attempt(['username'=>$userInfo['username'],'password'=>$data['y_password']])) {
            if(AuthAdmin::where('id',$userInfo['id'])->update(['password'=>bcrypt($data['password'])])){
                return $this->apiSuccess('修改成功！');
            }
            $this->apiError('修改失败！');
        }
        $this->apiError('原密码错误！');
    }
}
