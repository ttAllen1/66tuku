<?php
/**
 * 会员管理服务
 * @Description
 */

namespace Modules\Admin\Services\user;

use Carbon\Carbon;
use Exception;
use GatewayClient\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\AuthAdmin;
use Modules\Admin\Models\AuthUser;
use Modules\Admin\Models\Group;
use Modules\Admin\Models\User;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\AuthConfig;
use Modules\Api\Services\auth\TokenService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Models\UserPlatRechargeDate;

class UserService extends BaseApiService
{
    /**
     * @name 会员列表
     * @description
     * @param data Array 查询相关参数
     * @param data.page Int 页码
     * @param data.limit Int 每页显示条数
     **/
    public function index(array $data)
    {
//        $res = User::has('groups', '>=', 2)->get()->toArray();
//        $res = User::whereHas('groups', function ($query) {
//            $query->where('groups.id', 2);
//        })->get()->toArray();
        $plat_user_id = [];
        if (!empty($data['plat_name'])) {
            $platName = $data['plat_name'];
            $plat_user_id = DB::table('user_platforms')->where('plat_user_name', 'like', '%' . $platName . '%')->pluck('user_id')->toArray();
            if (!$plat_user_id) {
                return $this->apiSuccess('', [
                    'list'        => [],
                    'group'       => [],
                    'total'       => [],
                    'isAdminOk'   => false,
                    'isFinanceOk' => false,
                ]);
            }
        }

        try {
            $data['last_login_ip'] = $data['last_login_ip'] ?? ip2long($data['last_login_ip']);
            $last_login_at = $data['last_login_at'] ?? [];
            $sortBys = $data['sortBys'] ?? 0;
            if ($last_login_at) {
                $last_login_at[0] = Carbon::make($last_login_at[0])->format("Y-m-d 00:00:00");
                $last_login_at[1] = Carbon::make($last_login_at[1])->format("Y-m-d 23:59:59");
            }
            $model = User::query()
                ->with([
                    'vip'    => function ($query) {
                        $query->select('id', 'level_name');
                    },
                    'groups' => function ($query) {
                        $query->select('user_id', 'group_id', 'group_name');
                    },
                    'plat_quotas' => function($query) {
                        $query->select('id', 'user_id', 'plat_id', 'quota')
                            ->with(['plat'=>function($query) {
                                $query->select('id', 'name');
                            }]);
                    }
                ]);

            $list = $model
                ->whereIn('web_sign', ["48", "49"])
                ->when($data['account_name'], function ($query) use ($data) {
                    $query->where('account_name', 'like', '%' . $data['account_name'] . '%')
                        ->orderBy(DB::raw("CASE WHEN account_name = '" . $data['account_name'] . "' THEN 1 ELSE 2 END"));
                })
                ->when($data['id'], function ($query) use ($data) {
                    $query->where('id', $data['id']);
                })
                ->when($plat_user_id, function ($query) use ($plat_user_id) {
                    $query->whereIn('id', $plat_user_id);
                })
                ->when($data['nick_name'], function ($query) use ($data) {
                    $query->where('nickname', 'like', '%' . $data['nick_name'] . '%')
                        ->orderBy(DB::raw("CASE WHEN nickname = '" . $data['nick_name'] . "' THEN 1 ELSE 2 END"));
                })
                ->when($data['name'], function ($query) use ($data) {
                    $query->where('name', 'like', '%' . $data['name'] . '%')
                        ->orderBy(DB::raw("CASE WHEN name = '" . $data['name'] . "' THEN 1 ELSE 2 END"));
                })
                ->when($data['mobile'], function ($query) use ($data) {
                    $query->where('mobile', 'like', $data['mobile'] . '%');
                })
                ->when($data['invite_code'], function ($query) use ($data) {
                    $query->where('invite_code', $data['invite_code']);
                })
                ->when($data['last_login_ip'], function ($query) use ($data) {
                    if ($data['last_login_ip'] == '0.0.0.0') {
                        $data['last_login_ip'] = 0;
                    } else {
                        $data['last_login_ip'] = ip2long($data['last_login_ip']) ?: $this->ip2long6($data['last_login_ip']);
                    }
                    $query->where('last_login_ip', $data['last_login_ip']);
                })
                ->when($data['register_ip'], function ($query) use ($data) {
                    if ($data['register_ip'] == '0.0.0.0') {
                        $data['register_ip'] = 0;
                    } else {
                        $data['register_ip'] = ip2long($data['register_ip']) ?: $this->ip2long6($data['register_ip']);
                    }
                    $query->where('register_ip', $data['register_ip']);
                })
                ->when($data['device_type'] != 0, function ($query) use ($data) {
                    $query->where('last_login_device', $data['device_type']);
                })
                ->when($data['sex'] != 0, function ($query) use ($data) {
                    $query->where('sex', $data['sex']);
                })
                ->when($data['level_id'] != 0, function ($query) use ($data) {
                    $query->where('level_id', $data['level_id']);
                })
                ->when($data['status'] != 0, function ($query) use ($data) {
                    $query->where('status', $data['status']);
                })
                ->when($data['is_online'] != 0, function ($query) use ($data) {
                    $query->where('is_online', $data['is_online']);
                })
                ->when($data['account_type'] != 0, function ($query) use ($data) {
                    $query->where('account_type', $data['account_type']);
                })
                ->when($data['is_forbid_bet'] != 0, function ($query) use ($data) {
                    $query->where('is_forbid_bet', $data['is_forbid_bet']);
                })
                ->when($data['is_balance_freeze'] != 0, function ($query) use ($data) {
                    $query->where('is_balance_freeze', $data['is_balance_freeze']);
                })
                // 查询该分组下的用户
                ->when($data['group_id'] != 0, function ($query) use ($data) {
                    $query->whereHas('groups', function ($query) use ($data) {
                        $query->where('groups.id', $data['group_id']);
                    });
                })
                ->when($last_login_at, function ($query) use ($last_login_at) {
                    $query->whereBetween('register_at', $last_login_at);
                })
                ->when($sortBys == 0, function ($query) {
                    $query->latest('register_at');
                })
                ->when($sortBys == 1, function ($query) {
                    $query->orderByDesc('account_balance');
                })
//                ->where('system', 0)
                ->paginate($data['limit'])
                ->toArray();

            // 获取所有组别
            $groups = Group::query()->select('id', 'group_name')->get();

            // 获取当前管理员所属权限
            $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
            $isAdminOk = false;
            $isFinanceOk = false;
            $isServiceOk = false;
            $isGoldSearchOk = false;
            if (in_array($permiss, [1, 8])) {
                $isAdminOk = true; // 管理员
            }
            if ($permiss == 3) {
                $isFinanceOk = true; // 财务
            }
            if ($permiss == 2) {
                $isServiceOk = true; // 客服
            }
            if ($permiss == 7) {
                $isGoldSearchOk = true; // 金币查询
            }
            if ($list['data']) {
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['withdraw_lave_limit'] = 0 ;
                    foreach ($v['plat_quotas'] as $kk => $vv) {
                        $list['data'][$k]['withdraw_lave_limit'] += $vv['quota'];
                    }
                    $list['data'][$k]['last_login_ip'] = long2ip($v['last_login_ip']) ?: $this->long2ip6($v['last_login_ip']);
                    $list['data'][$k]['register_ip'] = long2ip($v['register_ip']) ?: $this->long2ip6($v['register_ip']);
                }
            }

            return $this->apiSuccess('', [
                'list'           => $list['data'],
                'group'          => $groups,
                'total'          => $list['total'],
                'isAdminOk'      => $isAdminOk,
                'isFinanceOk'    => $isFinanceOk,
                'isServiceOk'    => $isServiceOk,
                'isGoldSearchOk' => $isGoldSearchOk,
            ]);
        } catch (Exception $exception) {
            return $this->apiSuccess('', [
                'list'        => [],
                'group'       => [],
                'total'       => [],
                'isAdminOk'   => false,
                'isFinanceOk' => false,
            ]);
        }

    }

    /**
     * @name 添加
     * @description
     * @method  POST
     **/
    public function store(array $data)
    {
        try{
            DB::beginTransaction();
            // 获取当前管理员所属权限
            $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
            if (!in_array($permiss, [1, 8])) {
                return $this->apiError("此账号无权执行此操作");
            }
            $data['password'] = bcrypt($data['password']);
            $data['withdraw_lave_limit'] = $data['withdraw_lave_limit'] ?: 100;
            $data['register_at'] = date('Y-m-d H:i:s');
            $data['nickname'] = '49图库' . '_' . rand(100000, 999999);
            $data['avatar'] = AuthConfig::with('avatar')->first()->avatar->url;
            $data['new_avatar'] = AuthConfig::with('avatar')->first()->avatar->url;
            $data['chat_pwd'] = md5($data['password']);
            $data['chat_user'] = $this->str_rand(7);
            if (User::query()->where('account_name', $data['account_name'])->value('id')) {
                return $this->apiError('此账号已存在');
            }
            // 获取当前管理员所属权限
//        $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
//        if (!in_array($permiss, [1, 3, 8])) {
//            unset($data['account_balance']);
//        }

            if (empty($data['remark'])) {
                unset($data['remark']);
            }
            $data['created_at'] = date('Y-m-d H:i:s');
            // 生成唯一邀请码
            $data['invite_code'] = $this->getUserInviteCode();
            $group_id = $data['group_id'];
            unset($data['group_id']);
            $userId = User::query()->insertGetId($data);
            User::query()->find($userId)->groups()->attach($group_id);
            // 平台额度
            DB::table('user_plat_quotas')->insert([
                'user_id'       => $userId,
                'plat_id'       => 0,
                'quota'         => $data['withdraw_lave_limit'],
                'created_at'    => date('Y-m-d H:i:s')
            ]);
            DB::commit();
            return $this->apiSuccess();
        }catch (\Exception $exception) {
            DB::rollBack();
            return $this->apiError();
        }
//        return $this->commonCreate(User::query(), $data);
    }

    public function str_rand(int $length): string
    {
        //字符组合
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    /**
     * @name 修改页面
     * @description
     * @param id Int 管理员id
     * @return JSON
     **/
    public function edit(int $id)
    {
        return $this->apiSuccess('', AuthUser::find($id)->toArray());
    }

    /**
     * 调整状态
     * @param $id
     * @param array $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function status($id, array $data): JsonResponse
    {
//        dd($id, $data);
        if (isset($data['is_forbid_speak'])) {
            if ($data['is_forbid_speak'] == 1) {
                $data['forbid_speak_date'] = date('Y-m-d H:i:s');
            }
            $this->sendGateWay($id, $data['is_forbid_speak']);
        }

        return $this->commonStatusUpdate(User::query(), $id, $data);
    }

    private function sendGateWay($id, $is_forbid_speak)
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        Gateway::$registerAddress = '127.0.0.1:1238';
        $client_ids = [];
        foreach ($id as $v) {
            $clients = Gateway::getClientIdByUid($v);
            if ($clients) {
                foreach ($clients as $client) {
                    $client_ids[] = $client;
                }
            }
        }
        if ($client_ids) {
            if ($is_forbid_speak == 1) {
                Gateway::sendToAll(json_encode(['type' => 'forbid_speak', 'data' => ['user_id' => $id]]), $client_ids);
            } else if ($is_forbid_speak == 2) {
                Gateway::sendToAll(json_encode(['type' => 'open_speak', 'data' => ['user_id' => $id]]), $client_ids);
            }
        }
    }

    /**
     * @name 初始化密码/修改密码
     * @description
     **/
    public function updatePwd(int $id, string $pw = "123456")
    {
        // 获取当前管理员所属权限
        $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
        if (!in_array($permiss, [1, 8, 2])) {
            return $this->apiError("此账号无权执行此操作");
        }
        return $this->commonStatusUpdate(User::query(), $id, ['password' => bcrypt($pw)]);
    }

    /**
     * 修改资金密码
     * @param int $id
     * @param string $pw
     * @return JsonResponse
     * @throws ApiException
     */
    public function updateFundPwd(int $id, string $pw): JsonResponse
    {
        // 获取当前管理员所属权限
        $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
        if (!in_array($permiss, [1, 8, 2])) {
            return $this->apiError("此账号无权执行此操作");
        }
        return $this->commonStatusUpdate(User::query(), $id, ['fund_password' => bcrypt($this->_fund_password_salt . $pw)]);
    }

    /**
     * 用户禁言
     * @param $params
     * @return JsonResponse
     * @throws Exception
     */
    public function updateForbidSpeak($params)
    {
        if (isset($params['is_forbid_speak']) && in_array($params['is_forbid_speak'], [1, 2])) {
            if (!is_array($params['id'])) {
                $params['id'] = [$params['id']];
            }
            if ($params['is_forbid_speak'] == 1) {
                User::query()->whereIn('id', $params['id'])->update(['is_forbid_speak' => 2]);
            } else {
                User::query()->whereIn('id', $params['id'])->update(['is_forbid_speak'   => 1,
                                                                     'forbid_speak_date' => date('Y-m-d H:i:s')
                ]);
            }
            $this->sendGateWay($params['id'], ($params['is_forbid_speak'] == 1) ? 2 : 1);
        }

        return $this->apiSuccess('');
    }

    /**
     * 修改提交
     * @param int $id
     * @param array $data
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function update(int $id, array $data)
    {
        // 查询当前手机号是否属于这个人
        if (!empty($data['mobile'])) {
            $hasId = User::query()->where('mobile', $data['mobile'])->value('id');
            if ($hasId) {
                if ($hasId == $data['id']) {
                    unset($data['mobile']);
                } else {
                    return $this->apiError("此手机号已被其他用户绑定，请先解绑");
                }
            }
        }
        // 获取当前管理员所属权限
        $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
        if (!in_array($permiss, [1, 3, 8, 2, 7])) {
            unset($data['account_balance']);
            unset($data['withdraw_lave_limit']);
        }
        // 用户组别中间表
        if (isset($data['group_id']) && !empty($data['group_id'])) {
            User::query()->find($data['id'])->groups()->sync($data['group_id']);
        }
        if ($data['status'] == 1) {
            Redis::srem('blacklist_users', $data['id']);
        } else {
            Redis::sadd('blacklist_users', $data['id']);
        }
        if (isset($data['is_forbid_speak'])) {
            if ($data['is_forbid_speak'] == 1) {
                $data['forbid_speak_date'] = date('Y-m-d H:i:s');
            }
            $this->sendGateWay($id, $data['is_forbid_speak']);

        }
        unset($data['group_id']);
        // 判断用户提现额度是否更改
        if (isset($data['withdraw_lave_limit'])) {
            $withdraw_limit = DB::table('users')->where('id', $id)->value('withdraw_lave_limit');
            if ($data['withdraw_lave_limit'] > $withdraw_limit) {
                $data['withdraw_limit'] = $data['withdraw_limit'] + $data['withdraw_lave_limit'] - $withdraw_limit;
                UserPlatRechargeDate::query()->insert([
                    'user_id'    => $id,
                    'plat_id'    => 0,
                    'money'      => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $quotaModel = DB::table('user_plat_quotas')->where('user_id', $id)->where('plat_id', 0)->first();
                if ($quotaModel) {
                    DB::table('user_plat_quotas')
                        ->where('user_id', $id)
                        ->where('plat_id', 0)
                        ->update([
                            'quota' => $quotaModel->quota + $data['withdraw_lave_limit'] - $withdraw_limit,
                            'updated_at' => now()->format('Y-m-d H:i:s')
                        ]);
                } else {
                    DB::table('user_plat_quotas')
                        ->insert([
                            'user_id'    => $id,
                            'plat_id'    => 0,
                            'quota'      => $data['withdraw_lave_limit'],
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                }

//                $data['withdraw_limit'] += $data['withdraw_lave_limit'];
//                DB::table('users')->where('id', $id)->update(['withdraw_lave_limit'=>$data['withdraw_lave_limit'],'withdraw_limit'=>$data['withdraw_limit'] + $data['withdraw_lave_limit']]);
//                DB::table('users')->where('id', $id)->increment('withdraw_limit', $data['withdraw_limit'] + $data['withdraw_lave_limit']);
            }
        }

        return $this->commonUpdate(User::query(), $id, $data);
    }

    /**
     * 将用户踢下线
     * @param $id
     * @return void
     */
    public function logoutUser($id)
    {

    }

    public function user_id_name($name = '')
    {
        return $this->getUserIdByName(User::query(), $name);
    }

    public function user_id_full_name($name = '')
    {
        return $this->getUserIdByFullname(User::query(), $name);
    }

    public function id_by_nickname($name = '')
    {
        return $this->getUserIdByName(User::query(), $name, 'nickname');
    }

    /**
     * 管理员登录用户账号
     * @param $userId
     * @return JsonResponse
     * @throws ApiException
     */
    public function memberLogin($userId): JsonResponse
    {
        try {
            // 获取当前管理员所属权限
            $permiss = AuthAdmin::query()->where('id', auth('auth_admin')->id())->value('group_id');
            if (!in_array($permiss, [1, 8])) {
                return $this->apiError("此账号无权执行此操作");
            }
            $userInfo = \Modules\Api\Models\User::query()->where('id', $userId)->first();
            $config = (new ConfigService())->getConfigs(['h5_url']);
            $token = (new TokenService)->setToken2($userInfo);
            if (!$token) {
                return $this->apiError('登录失败');
            }
            return $this->apiSuccess('登录成功', [
                'h5_url' => $config['h5_url'],
                'token'  => $token,
            ]);
        } catch (\Exception $exception) {
            if ($exception instanceof ApiException) {
                return $this->apiError($exception->getMessage());
            }
            return $this->apiError('登录失败');
        }
    }

    /**
     * 修改额度
     * @param array $params
     * @return JsonResponse
     */
    public function user_quotas(array $params): JsonResponse
    {
        if ($params) {
            foreach ($params as $param) {
                DB::table('user_plat_quotas')->where('id', $param['id'])->update(['quota'=>$param['quota'], 'updated_at'=>now()->format('Y-m-d H:i:s')]);
            }
        }
        return $this->apiSuccess();
    }
}
