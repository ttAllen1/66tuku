<?php

/**
 * 管理员服务
 * @Description
 */

namespace Modules\Admin\Services\admin;

use Earnp\GoogleAuthenticator\GoogleAuthenticator;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Models\AuthAdmin as AuthAdminModel;
use Modules\Admin\Services\BaseApiService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AdminService extends BaseApiService
{
    /**
     * @name 管理员列表
     * @description
     * @param  data Array 查询相关参数
     * @param  data.page Int 页码
     * @param  data.limit Int 每页显示条数
     * @param  data.username String 账号
     * @param  data.group_id Int 权限组ID
     * @param  data.project_id int 项目ID
     * @param  data.status Int 状态:0=禁用,1=启用
     * @param  data.created_at Array 创建时间
     * @param  data.updated_at Array 更新时间
     * @return JSON
     **/
    public function getList(array $data)
    {
        $model = AuthAdminModel::query();
        $model = $this->queryCondition($model,$data,'username');
        if (isset($data['group_id']) && $data['group_id'] > 0){
            $model = $model->where('group_id',$data['group_id']);
        }
        if (isset($data['project_id']) && $data['project_id'] > 0){
            $model = $model->where('project_id',$data['project_id']);
        }
        $list = $model->with([
                'auth_groups'=>function($query){
                    $query->select('id','name');
                },
                'auth_projects'=>function($query){
                    $query->select('id','name');
                }
            ])
            ->orderBy('id','desc')
            ->paginate($data['limit'])
            ->toArray();
        return $this->apiSuccess('',[
            'list'=>$list['data'],
            'total'=>$list['total']
        ]);
    }

    /**
     *  创建
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {
        $data['password'] = bcrypt($data['password']);
        $id = AuthAdminModel::query()->insertGetId($data);

        $google = $this->getGoogleQrCode($id);
        AuthAdminModel::query()->where('id', $id)->update([
            'google_secret'  => $google['google_secret'],
            'google_qrcode'  => $google['google_qrcode'],
        ]);

        return $this->apiSuccess('创建成功');
    }

    /**
     * @name 修改页面
     * @description
     * @param  id Int 管理员id
     * @return JSON
     **/
    public function edit(int $id){
        return $this->apiSuccess('',AuthAdminModel::select('id','name','group_id','phone','username','project_id')->find($id)->toArray());
    }
    /**
     * @name 修改提交
     * @description
     * @param  data Array 修改数据
     * @param  daya.id Int 管理员id
     * @param  daya.name String 名称
     * @param  daya.phone String 手机号
     * @param  daya.username String 账号
     * @param  daya.group_id Int 权限组ID
     * @param  data.project_id int 项目ID
     * @return JSON
     **/
    public function update(int $id,array $data){
        if (empty($data['password'])) {
            unset($data['password']);
        }
        return $this->commonUpdate(AuthAdminModel::query(),$id,$data);
    }
    /**
     * @name 调整状态
     * @description
     * @param  data Array 调整数据
     * @param  id Int 管理员id
     * @param  data.status Int 状态（0或1）
     * @return JSON
     **/
    public function status(int $id,array $data){
        return $this->commonStatusUpdate(AuthAdminModel::query(),$id,$data);
    }
    /**
     * @name 初始化密码
     * @return JSON
     **/
    public function updatePwd(int $id){
        return $this->commonStatusUpdate(AuthAdminModel::query(),$id,['password'=>bcrypt(config('admin.update_pwd'))],'密码初始化成功！','密码初始化失败，请重试！');
    }

    /**
     * 生成谷歌二维码
     * @param $id
     * @return JsonResponse
     */
    public function generate($id): JsonResponse
    {
        $google = $this->getGoogleQrCode($id);
        AuthAdminModel::query()->where('id', $id)->update([
           'google_secret'  => $google['google_secret'],
           'google_qrcode'  => $google['google_qrcode'],
        ]);

        return $this->apiSuccess('生成二维码成功');
    }

    /**
     * 获取谷歌验证二维码
     * @param $adminId
     * @return array
     */
    private function getGoogleQrCode($adminId): array
    {
        // 创建谷歌验证码
        $createSecret = GoogleAuthenticator::CreateSecret();
        $remark = urlencode('49管理后台：45.61.244.7');
        $qrCodeUrl = 'otpauth://totp/' . $remark . '?secret=' . $createSecret['secret'];
        QrCode::encoding('UTF-8')->size(180)->generate($qrCodeUrl, public_path('storage/google_qrcode/qrcode'.$adminId.'.svg'));

        // 需要将密钥和二维码地址存到admin_user表中 ZM3RXJBBFVH4VTUQ
        return ['google_secret' => $createSecret['secret'], 'google_qrcode'=>'/storage/google_qrcode/qrcode'.$adminId.'.svg'];
    }
}
