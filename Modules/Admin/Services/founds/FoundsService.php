<?php
/**
 *资金管理服务
 * @Description
 */
namespace Modules\Admin\Services\founds;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Events\OnceAccount;
use Modules\Admin\Events\ReopenAccount;
use Modules\Admin\Models\AuthConfig;
use Modules\Admin\Models\StationMsg;
use Modules\Admin\Models\UserMessage;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\IncomeApply;
use Modules\Api\Models\Platform;
use Modules\Api\Models\User;
use Modules\Api\Models\UserBet;
use Modules\Api\Models\UserPlatform;
use Modules\Api\Models\UserPlatRecharge;
use Modules\Api\Models\UserPlatWithdraw;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\platform\PlatformService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\UserPlatRechargeDate;

class FoundsService extends BaseApiService
{
    /**
     * @param $data
     * @return JsonResponse
     */
    public function platforms_list($data): JsonResponse
    {
        $list = Platform::query()
            ->when($data['status'] != -2, function($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->latest()
            ->paginate()->toArray();

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total']
        ]);
    }

    /**
     * 修改平台
     * @param $id
     * @param array $data
     * @return JsonResponse|null
     */
    public function platforms_update($id,array $data): ?JsonResponse
    {
        return $this->commonUpdate(Platform::query(),$id,$data);
    }

    /**
     * 添加平台
     * @param array $data
     * @return JsonResponse
     */
    public function platforms_store(array $data): JsonResponse
    {
        return $this->commonCreate(Platform::query(), $data);
    }

    /**
     * 删除平台
     * @param $id
     * @return JsonResponse|null
     */
    public function platforms_delete($id): ?JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(Platform::query(),$id);
    }

    /**
     * 会员平台列表
     * @param $data
     * @return JsonResponse
     */
    public function user_platform_list($data): JsonResponse
    {
        $list = UserPlatform::query()
            ->when($data['platforms'], function($query) use ($data) {
                $query->where('plat_id', $data['platforms']);
            })
            ->when($data['status'] != -2, function($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['plat_user_name'], function($query) use ($data) {
                $query->where('plat_user_name', $data['plat_user_name']);
            })
            ->when($data['account_name'], function($query) use ($data) {
                $query->whereHas('user', function($query) use ($data) {
                    $query->where('account_name', 'like', '%'.$data['account_name'].'%');
                });
            })
            ->with(['plats', 'user'=>function($query) {
                $query->select(['id', 'nickname', 'account_name']);
            }])
            ->latest()
            ->paginate()->toArray();
        // 所有平台
        $platforms = Platform::query()->where('status', 1)->latest()->get();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total'],
            'platforms'     => $platforms
        ]);
    }

    /**
     * 会员平台更新
     * @param $id
     * @param array $data
     * @return JsonResponse
     */
    public function user_platform_update($id,array $data): JsonResponse
    {
        return $this->commonUpdate(UserPlatform::query(),$id,$data);
    }

    /**
     * 会员平台删除
     * @param $id
     * @return JsonResponse|null
     * @throws ApiException
     */
    public function user_platform_delete($id): ?JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        try{
            $userPlat = UserPlatform::query()->where('id', $id)->select(['id', 'plat_id', 'user_id'])->firstOrFail()->toArray();
            // 判断当前会员在此平台是否有待提现操作
            $isExistId = UserPlatWithdraw::query()->where('user_id', $userPlat['user_id'])
                ->where('plat_id', $userPlat['plat_id'])
                ->where('status', 0)
                ->value('id');
            if ($isExistId) {
                return $this->apiError('此用户在此平台有待审核的提现，禁止删除');
            }
            return $this->commonDestroy(UserPlatform::query(),$id);
        }catch (\Exception $exception) {
            return $this->apiError($exception->getMessage() ?? "操作有误，请刷新重试");
        }

    }

    /**
     * 会员充值列表
     * @param $data
     * @return JsonResponse
     */
    public function user_recharge_list($data): JsonResponse
    {
        $list = UserPlatRecharge::query()
            ->when($data['platforms'], function($query) use ($data) {
                $query->where('plat_id', $data['platforms']);
            })
            ->when($data['status'] != -2, function($query) use ($data) {
                $query->where('status', $data['status']);
            })
//            ->with(['user_plats.plats', 'user'=>function($query) {
//                $query->select(['id', 'nickname']);
//            }])
            ->when($data['trade_no'], function($query) use ($data) {
                $query->where('trade_no', $data['trade_no']);
            })
            ->when($data['account_name'], function($query) use ($data) {
                $query->whereHas('user', function($query) use ($data) {
                    $query->where('account_name', 'like', '%'.$data['account_name'].'%');
                });
            })
            ->with(['plats', 'user'=>function($query) {
                $query->select(['id', 'nickname', 'account_name']);
            }])
            ->latest()
            ->paginate()->toArray();

        $user_id = [];
        $plat_id = [];
        foreach ($list['data'] as $v) {
            $user_id[] = $v['user_id'];
            $plat_id[] = $v['plat_id'];
        }
        $arr = UserPlatform::query()->whereIn('plat_id', $plat_id)->orWhereIn('user_id', $user_id)->select(['id', 'plat_id', 'user_id', 'plat_user_name', 'plat_user_account', 'status'])->get()->toArray();
        foreach ($list['data'] as $k => $v) {
            foreach ($arr as $kk => $vv) {
                if ($v['user_id'] == $vv['user_id'] && $v['plat_id'] == $vv['plat_id']) {
                    $list['data'][$k]['user_plats'] = $vv;
                }
            }
        }

        // 所有平台
        $platforms = Platform::query()->latest()->get();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total'],
            'platforms'     => $platforms
        ]);
    }

    /**
     * 会员充值更新
     * @param $id
     * @param array $data
     * @return JsonResponse|null
     */
    public function user_recharge_update($id,array $data): ?JsonResponse
    {
        return $this->commonUpdate(UserPlatRecharge::query(),$id,$data);
    }

    /**
     * 会员充值审核 平台-》图库 *
     * @param $id
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function user_recharge_update_status($id, $data): JsonResponse
    {
        try {
            $status = $data['status'];
            $isSystem = $data['isSystem'] ?? 0;
            $info = UserPlatRecharge::query()->whereIn('id', $id)
                ->with(['plats'])->get();
            if ($info->isEmpty()) {
                return $this->apiSuccess();
            }
            $info = $info->toArray();
            foreach ($info as $k => $recharge) {
                $info[$k]['user_plats'] = UserPlatform::query()->where('user_id', $recharge['user_id'])->where('plat_id', $recharge['plat_id'])->where('status', 1)->firstOrFail()->toArray();
            }
            foreach ($info as $k => $v) {
                if ($status==-1) {
                    UserPlatRecharge::query()->where('id', $v['id'])->update(['status'=>$status]);
                    return $this->apiSuccess('审核成功');
                }

                // UserRecharge#UserName#Amount#Key
                $token = trim($v['plats']['token']);
                $sign = strtolower(md5('UserWithdraw#'.$v['user_plats']['plat_user_account'].'#'.$v['money'].'#'.$token));

                $response = Http::withOptions([
                    'verify'=>false
                ])->timeout(5)->retry(3, 100)->get(sprintf($v['plats']['withdraw_api'], $v['user_plats']['plat_user_account'], $v['money'], $sign, '49'));
                if ($response->status() != 200) {
                    throw new CustomException(['message'=>'与彩票平台通信中断']);
                }
                $res = json_decode($response->body(), true);
                if ( $res['Status'] != 0 ) {
                    throw new CustomException(['message'=>'平台：'.$res['Message']]);
                }
                User::query()->where('id', $v['user_id'])->increment('account_balance', $v['final_money']);
                UserPlatRecharge::query()->where('id', $v['id'])->update(['status'=>$status]);
                // 金币记录
                (new ActivityService())->modifyAccount($v['user_id'], $isSystem ? 'revoke_plat_recharge' : 'plat_recharge', $v['final_money']);
                // 发送站内信
                if (!$isSystem) {
                    $msg['title']       = '恭喜充值成功';
                    $msg['content']     = '恭喜您，您的平台'.$v['plats']['name'].'充值已成功到账 '. $v['final_money'].'元！！！请前往个人中心查看，49图库祝您生活愉快～';
                    $msg['type']        = 2;
                    $msg['appurtenant'] = 2;
                    $msg['created_at']  = date('Y-m-d H:i:s');
                    $msgId = StationMsg::query()->insertGetId($msg);
                    $userMsg = [];
                    $userMsg['user_id'] = $v['user_id'];
                    $userMsg['msg_id'] = $msgId;
                    UserMessage::query()->insert($userMsg);
                    UserPlatWithdraw::query()->where('id', $id)->update(['send_msg_id'=>$msgId]);
                }
            }
        }catch (CustomException $exception) {
            Log::error('管理员审核用户充值失败', ['message'=>$exception->getMessage()]);
            return $this->apiError($exception->getMessage());
        }catch (\Exception $exception) {
            Log::error('管理员审核用户充值失败', ['message'=>$exception->getMessage()]);
            return $this->apiError('审核失败');
        }
        return $this->apiSuccess('审核成功');
    }

    /**
     * 会员充值删除
     * @param $id
     * @return JsonResponse|null
     */
    public function user_recharge_delete($id): ?JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(UserPlatRecharge::query(),$id);
    }

    /**
     * 会员提现列表
     * @param $data
     * @return JsonResponse
     */
    public function user_withdraw_list($data): JsonResponse
    {
        $list = UserPlatWithdraw::query()
            ->when($data['platforms'], function($query) use ($data) {
                $query->where('plat_id', $data['platforms']);
            })
            ->when($data['status'] != -3, function($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['trade_no'], function($query) use ($data) {
                $query->where('trade_no', $data['trade_no']);
            })
            ->when($data['account_name'], function($query) use ($data) {
                $query->whereHas('user', function($query) use ($data) {
                    $query->where('account_name', 'like', '%'.$data['account_name'].'%');
                });
            })
//            ->with(['user_plats', 'plats', 'user'=>function($query) {
//                $query->select(['id', 'nickname']);
//            }])
            ->with(['plats', 'user'=>function($query) {
                $query->select(['id', 'nickname', 'account_name', 'is_balance_freeze', 'register_area', 'last_login_area', 'withdrawal_num', 'register_at']);
            }])
            ->latest()
            ->paginate()->toArray();
        $user_id = [];
        $plat_id = [];
        foreach ($list['data'] as $k => $v) {
            $user_id[] = $v['user_id'];
            $plat_id[] = $v['plat_id'];
        }
        $arr = UserPlatform::query()->whereIn('plat_id', $plat_id)->orWhereIn('user_id', $user_id)->select(['id', 'plat_id', 'user_id', 'plat_user_name', 'plat_user_account', 'status'])->get()->toArray();
        foreach ($list['data'] as $k => $v) {
            foreach ($arr as $kk => $vv) {
                if ($v['user_id'] == $vv['user_id'] && $v['plat_id'] == $vv['plat_id']) {
                    $list['data'][$k]['user_plats'] = $vv;
                }
            }
        }

        // 提现总额
        $totalWithdraw = Redis::get('total_user_withdraws') ?: 0;

        // 各平台提现今日统计
        $statistics = UserPlatWithdraw::query()
            ->with(['plats'=>function($query) {
                $query->select('id', 'name');
            }])
            ->select('plat_id')
            ->where('status', 1)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(money) as total_money')
            ->whereDate('created_at', date('Y-m-d'))
            ->groupBy('plat_id')
            ->get();

        // 所有平台
        $platforms = Platform::query()->latest()->get();
        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total'],
            'totalWithdraw' => $totalWithdraw,
            'platforms'     => $platforms,
            'statistics'   => $statistics
        ]);
    }

    /**
     * 会员提现信息修改
     * @param $id
     * @param $data
     * @return JsonResponse|null
     */
    public function user_withdraw_update($id, $data): ?JsonResponse
    {
        $user_plats = json_decode($data['user_plats'], true);
//        dd($user_plats);
//        $UserPlatform::where('id', $user_plats['id'])->update($user_plats);
        return $this->commonUpdate(UserPlatform::query(),$data['id'],$data);
    }

    /**
     * 会员提现审核 图库-》平台 ok 入账接口
     * @param $id
     * @param array $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function user_withdraw_update_status($id,array $data): JsonResponse
    {
//        throw new CustomException(['message'=>'审核功能升级中，此功能稍后开放']);
//        if (!in_array(45187, $id)) {
//            throw new CustomException(['message'=>'审核功能升级中，此功能稍后开放']);
//        }

        $withdraws = [];
        try{
            DB::beginTransaction();
            $status = $data['status'];
            $withdraws = UserPlatWithdraw::query()
                ->lockForUpdate()
                ->whereIn('id', $id)
                ->with(['plats'])->select(['id', 'user_id', 'plat_id', 'money', 'rate', 'final_money', 'nums', 'status'])->get();
            if ($withdraws->isEmpty()) {
                DB::rollBack();
                return $this->apiSuccess('');
            }
            $withdraws = $withdraws->toArray();
            foreach ($withdraws as $k => $withdraw) {
                $withdraws[$k]['user_plats'] = UserPlatform::query()->where('user_id', $withdraw['user_id'])->where('plat_id', $withdraw['plat_id'])->where('status', 1)->firstOrFail()->toArray();
            }
            foreach ($withdraws as $k => $withdraw) {
                // 第一次审核
                if ($withdraw['status'] == 0) {
                    // 审核通过
                    if ($status == 1) {
                        // 再次检验该用户余额是否充足
                        $userIsBalanceFreeze = User::query()->find($withdraw['user_id'], ['is_balance_freeze', 'status']);
                        if ($userIsBalanceFreeze['is_balance_freeze'] ==1 ) {
                            throw new CustomException(['message'=>'该用户资金账户已被冻结，禁止通过操作']);
                        }
                        if ($userIsBalanceFreeze['status'] ==2 ) {
                            throw new CustomException(['message'=>'该用户账户已被禁用，禁止通过操作']);
                        }

                        $token = trim($withdraw['plats']['token']);
                        $sign = strtolower(md5('UserRecharge#'.$withdraw['user_plats']['plat_user_account'].'#'.$withdraw['final_money'].'#'.$token));
                        $response = Http::withOptions([
                            'verify'=>false
                        ])->timeout(5)->retry(3, 100)->get(sprintf($withdraw['plats']['recharge_api'], $withdraw['user_plats']['plat_user_account'], $withdraw['final_money'], $sign, '49'));
                        if ($response->status() != 200) {
                            throw new CustomException(['message'=>'与彩票平台通信中断']);
                        }
                        $res = json_decode($response->body(), true);
                        if ( $res['Status'] != 0 ) {
                            throw new CustomException(['message'=>'平台：'.$res['Message']]);
                        }
                        if ($withdraw['nums'] ==0) {
                            $count = UserPlatWithdraw::query()->where('user_id', $withdraw['user_id'])->where('status', 1)->count('id');
                            UserPlatWithdraw::query()->where('id', $id)->update(['status'=>$data['status'], 'nums'=>$count+1]); // todo
                            DB::table('users')->where('id', $withdraw['user_id'])->update(['withdrawal_num'=>$count+1]);
                        } else {
                            UserPlatWithdraw::query()->where('id', $id)->increment('nums', 1, ['status'=>$data['status']]);
                            DB::table('users')->where('id', $withdraw['user_id'])->update(['withdrawal_num'=>$withdraw['nums']+1]);
                        }
                        DB::table('users')->where('id', $withdraw['user_id'])->decrement('withdraw_lave_limit', $withdraw['money']);
                        // xinbaio shan
                        $globalEquate = DB::table('user_plat_quotas')
                            ->where('user_id', $withdraw['user_id'])
                            ->where('plat_id', 0)
                            ->value('quota');
                        if ($globalEquate>=$withdraw['money']) {
                            DB::table('user_plat_quotas')
                                ->where('user_id', $withdraw['user_id'])
                                ->where('plat_id', 0)
                                ->decrement('quota', $withdraw['money']);
                        } else if ($globalEquate>0) {
                            DB::table('user_plat_quotas')
                                ->where('user_id', $withdraw['user_id'])
                                ->where('plat_id', 0)
                                ->update(['quota'=>0]);
                            DB::table('user_plat_quotas')
                                ->where('user_id', $withdraw['user_id'])
                                ->where('plat_id', $withdraw['plat_id'])
                                ->decrement('quota', $withdraw['money']-$globalEquate);
                        } else {
                            DB::table('user_plat_quotas')
                                ->where('user_id', $withdraw['user_id'])
                                ->where('plat_id', $withdraw['plat_id'])
                                ->decrement('quota', $withdraw['money']);
                        }


                        $total_user_withdraws = Redis::get('total_user_withdraws');
                        Redis::set('total_user_withdraws', ($total_user_withdraws+$withdraw['money']));
//                        User::query()->where('id', $withdraw['user_id'])->decrement('account_balance', $withdraw['money']);
                        // 金币记录
                        (new ActivityService())->modifyAccount($withdraw['user_id'], 'plat_withdraw', $withdraw['money']);
                        // 发送站内信
                        $msg['title']       = '恭喜提现成功';
                        $msg['content']     = '恭喜您，您的平台'.$withdraw['plats']['name'].'提现已成功到账 '. $withdraw['final_money'].'元！！！点击➡️<a style="color:red" href="'.$withdraw['plats']['website'].'">官网</a>⬅️立即查看，【注：该彩金只能用于此平台投注六合彩】。49图库祝您生活愉快～';
                        $msg['type']        = 2;
                        $msg['appurtenant'] = 2;
                        $msg['created_at']  = date('Y-m-d H:i:s');
                        $msgId = StationMsg::query()->insertGetId($msg);
                        $userMsg = [];
                        $userMsg['user_id'] = $withdraw['user_id'];
                        $userMsg['msg_id'] = $msgId;
                        UserMessage::query()->insert($userMsg);
                        UserPlatWithdraw::query()->where('id', $id)->update(['send_msg_id'=>$msgId]);
                    } else {
                        // 初次审核不通过 无需处理
                        // 退还图库会员余额
                        User::query()->where('id', $withdraw['user_id'])
                            ->update([
                                'account_balance'   => DB::raw('account_balance + ' . $withdraw['money']),
                                'withdrawal_total'   => DB::raw('withdrawal_total - ' . $withdraw['money']),
                            ]);
                        UserPlatWithdraw::query()->where('id', $id)->update(['status'=>$data['status']]);
                        (new ActivityService())->modifyAccount($withdraw['user_id'], 'plat_withdraw_cancel', $withdraw['money']);
                    }
                    // 二次审核 且 初次审核为 通过
                } else if ($withdraw['status'] == 1){
                    throw new CustomException(['message'=>'暂不支持重复审核']);
                    // 再次审核为不通过 返回之前扣除的余额
                    if ($status == -1) {
                        User::query()->where('id', $withdraw['user_id'])->increment('account_balance', $withdraw['final_money']);
                    } else if ($status == 1) {
                        // 再次审核为通过 无需处理
                    }
                    // 二次审核 且 初次审核为 不通过
                } else if ($withdraw['status'] == -1) {
                    throw new CustomException(['message'=>'暂不支持重复审核']);
                    // 再次审核为通过 减少余额
                    $userBalance = User::query()->find($withdraw['user_id'], ['account_balance']);
                    if ($userBalance['account_balance'] < $withdraw['final_money']) {
                        throw new CustomException(['message'=>'用户账户余额不足，审核通过失败']);
                    }
                    User::query()->where('id', $withdraw['user_id'])->decrement('account_balance', $withdraw['final_money']);
                }
            }
        }catch (ModelNotFoundException $exception) {
            // 不管审核通过如否，都置为不通过 资金返回
            if ($withdraws) {
                foreach($withdraws as $k => $withdraw) {
                    User::query()->where('id', $withdraw['user_id'])->increment('account_balance', $withdraw['money']);
                    UserPlatWithdraw::query()->where('id', $id)->update(['status'=>-1]);
                }
            }
            DB::commit();
            return $this->apiError('系统检测到关键数据缺失，已退还该用户余额');
        }catch (CustomException $exception) {
            DB::rollBack();
            Log::error('管理员审核用户提现失败', ['message'=>$exception->getMessage()]);
            return $this->apiError($exception->getMessage());
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error('管理员审核用户提现失败', ['message'=>$exception->getMessage()]);
            return $this->apiError('审核失败');
        }
        DB::commit();
        return $this->apiSuccess('审核成功');
    }

    /**
     * 撤回
     * @param $id
     * @return JsonResponse
     * @throws ApiException
     */
    public function user_withdraw_update_revoke($id): JsonResponse
    {
//        throw new CustomException(['message'=>'撤回功能升级中，此功能稍后开放']);
        try{
            DB::beginTransaction();
            $res = UserPlatWithdraw::query()
                ->lockForUpdate()
                ->select('id', 'user_id', 'plat_id', 'money', 'send_msg_id', 'status')
                ->findOrFail($id)->toArray();
            if ($res['status'] != 1) {
                DB::rollBack();
                return $this->apiError('此状态禁止撤回');
            }
            $user_plat_id = UserPlatform::query()->where('user_id', $res['user_id'])->where('plat_id', $res['plat_id'])->value('id');
            // 调用充值接口
            $data['quota'] = $res['money'];
            $data['user_plat_id'] = $user_plat_id;
            $data['user_id'] = $res['user_id'];
            $data['is_system'] = true;
            $userRechargeId = (new PlatformService())->recharge($data);       // 添加充值记录
            $this->user_recharge_update_status([$userRechargeId], ['status'=>1, 'isSystem'=>1]);           // 调用平台充值接口
//            UserPlatWithdraw::query()->where('id', $id)->update(['status'=>-2]);
            UserPlatWithdraw::query()->where('id', $id)->decrement('nums', 1, ['status'=>-2]);
            // 减少提现总额
            $total_user_withdraws = Redis::get('total_user_withdraws');
            // 返回用户提现额度
            DB::table('users')->where('id', $res['user_id'])->increment('withdraw_lave_limit', $res['money']);
            DB::table('user_plat_quotas')->where('id', $res['user_id'])->where('plat_id', $res['plat_id'])->increment('quota', $res['money']);
            Redis::set('total_user_withdraws', ($total_user_withdraws-$res['money']));
            // 隐藏相关站内信
            StationMsg::query()->where('id', $res['send_msg_id'])->update(['status' => 2]);
        }catch (\Exception $exception) {
            DB::rollBack();
            if ($exception instanceof ModelNotFoundException) {
                return $this->apiError('操作数据不存在');
            }
            return $this->apiError('撤回失败：'.$exception->getMessage());
        }
        DB::commit();
        return $this->apiSuccess('撤回成功');
    }

    public function user_withdraw_delete($id): ?JsonResponse
    {
        if (!is_array($id)) {
            $id = [$id];
        }
        return $this->commonDestroy(UserPlatWithdraw::query(),$id);
    }

    /**
     * 额度配置信息
     * @return JsonResponse
     */
    public function quota_list(): JsonResponse
    {
        $configs = AuthConfig::query()->select(['recharge_open', 'withdraw_open', 'withdraw_num','withdraw_min_each', 'withdraw_max_each', 'withdraw_max', 'recharge_rate', 'withdraw_rate', 'quota_list'])->first();
        $configs = $configs->toArray();
        return $this->apiSuccess('',[
            'list'          => $configs,
        ]);
    }

    public function quota_update(array $data): ?JsonResponse
    {
        $id = 1;
        $arr = [];
        foreach($data['quota_list'] as $k => $v) {
            $arr[] = json_decode($v, true);
        }
        $data['quota_list'] = json_encode($arr);

        return $this->commonUpdate(AuthConfig::query(),$id,$data);
    }

    /**
     * 会员投注列表
     * @param array $params
     * @return JsonResponse
     */
    public function bet_list(array $params): JsonResponse
    {
        $list = UserBet::query()
            ->when($params['status'] != -2, function($query) use ($params) {
                $query->where('status', $params['status']);
            })
            ->when($params['win_status'] != -2, function($query) use ($params) {
                $query->where('win_status', $params['win_status']);
            })
            ->when($params['lotteryType'], function($query) use ($params) {
                $query->where('lotteryType', $params['lotteryType']);
            })
            ->when($params['account_name'], function($query) use ($params) {
                $query->whereHas('user', function($query) use ($params) {
                    $query->where('account_name', 'like', '%'.$params['account_name'].'%');
                });
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'account_name']);
            }])
            ->latest()
            ->paginate($params['limit'])->toArray();

        $type = Redis::get('forecast_bet_win_type');
        if (!$type) {
            $type = DB::table('auth_activity_configs')->where('k', 'forecast_bet_win_type')->value('v');
        }

        $totalBetMoneyYesterday = DB::table('user_bets')
            ->whereDate('created_at', Carbon::yesterday()->format('Y-m-d'))
            ->sum('each_bet_money');
        $totalBetMoneyToday = DB::table('user_bets')
            ->whereDate('created_at', Carbon::today()->format('Y-m-d'))
            ->sum('each_bet_money');
        $totalWinMoneyYesterday = DB::table('user_bets')
            ->where('status', 1)
            ->whereDate('created_at', Carbon::yesterday()->format('Y-m-d'))
            ->sum('win_money');
        $totalWinMoneyToday = DB::table('user_bets')
            ->where('status', 1)
            ->whereDate('created_at', Carbon::today()->format('Y-m-d'))
            ->sum('win_money');
        $profitYesterday = number_format(DB::table('user_bets')
                ->whereIn('status', [-1, 1])
                ->whereDate('created_at', Carbon::yesterday()->format('Y-m-d'))
                ->sum('each_bet_money') - $totalWinMoneyYesterday, 2);
        $profitToday = number_format(DB::table('user_bets')
                ->whereIn('status', [-1, 1])
                ->whereDate('created_at', Carbon::today()->format('Y-m-d'))
                ->sum('each_bet_money') - $totalWinMoneyToday, 2);

        $profits = [
            [
                "date"=> "昨日",
                "list" => [
                    'totalBetMoney' => $totalBetMoneyYesterday,  // 总投注金额
                    'totalWinMoney' => $totalWinMoneyYesterday,
                    'profit'        => $profitYesterday,
                ]
            ],
            [
                "date"=> "今日",
                "list" => [
                    'totalBetMoney'     => $totalBetMoneyToday,
                    'totalWinMoney'     => $totalWinMoneyToday,
                    'profit'            => $profitToday,
                ]
            ],
        ];

        $totalBetMoney = DB::table('user_bets')
            ->sum('each_bet_money');
        $totalWinMoney = DB::table('user_bets')->where('status', 1)->sum('win_money');
        $profit = number_format(DB::table('user_bets')
                ->whereIn('status', [-1, 1])
                ->sum('each_bet_money') - $totalWinMoney, 2);

        return $this->apiSuccess('', [
            'list'         => $list['data'],
            'total'        => $list['total'],
            'type'         => $type,
            'award_profit' => $profits,
            'totalBetMoney' => $totalBetMoney,
            'totalWinMoney' => $totalWinMoney,
            'profit'        => $profit
        ]);
    }

    /**
     * 用户投注--入账操作
     * @param array $params
     * @return JsonResponse
     */
    public function bet_account_update(array $params): JsonResponse
    {
        try{
            DB::beginTransaction();
            $userBets = UserBet::query()
                ->whereIn('id', $params['id'])
                ->select(['id', 'user_id', 'win_status', 'status', 'win_money'])
                ->get()->toArray();

            foreach ($userBets as $v) {
                if ($v['win_status'] != 1 || $v['status']!=1) {
                    return $this->apiError('ID：'.$v['id'].'的资金状态不为可提状态');
                }
            }
            foreach ($userBets as $v) { // 金币记录 余额 都需要动
                $userGoldData = [];
                $userGoldData['user_id'] = $v['user_id'];
                $userGoldData['type'] = 14;
                $userGoldData['gold'] = $v['win_money'];
                $userGoldData['symbol'] = '+';
                $userGoldData['user_bet_id'] = $v['id'];
                $userGoldData['balance'] = DB::table('users')->where('id', $v['user_id'])->value('account_balance') + $v['win_money'];
                $userGoldData['created_at'] = date("Y-m-d H:i:s");
                DB::table('user_gold_records')->insert($userGoldData);
                DB::table('users')->where('id', $v['user_id'])->increment('account_balance', $v['win_money']);
            }
            UserBet::query()
                ->whereIn('id', $params['id'])
                ->update(['win_status'=>2]);
        }catch (\Exception $exception) {
            DB::rollBack();
        }
        DB::commit();
        return $this->apiSuccess();
    }

    /**
     * 一键入账
     * @return JsonResponse
     * @throws ApiException
     */
    public function bet_once_account_update(): JsonResponse
    {
        try{
            $userBets = UserBet::query()
                ->lockForUpdate()
                ->where(['status'=>1, 'win_status'=>1])
                ->select(['id', 'user_id', 'win_money'])
                ->orderBy('created_at')
                ->get();
            if ( $userBets->isEmpty() ) {
                Redis::expire('once_account_adminId', 0);
                return $this->apiSuccess('暂无需入账的记录');
            }
            $ttl = Redis::ttl('once_account_adminId');
            if ( $ttl > 0) {
                return $this->apiSuccess('异步队列已包含此任务，请于'.$ttl.'秒后，重新投递任务');
            }
            event(new OnceAccount($userBets));

            return $this->apiSuccess('异步队列任务投递成功，请耐心等待');
        } catch(\Exception $exception) {
            Log::error('异步队列任务投递失败', ['message'=>$exception->getMessage(), 'codeLine'=>$exception->getLine()]);

            return $this->apiError('异步队列任务投递失败，请联系开发');
        }
    }

    /**
     * 重开奖项
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function bet_reopen_account_update($params): JsonResponse
    {
        $lotteryType = $params['lotteryType'];
        if (!in_array($lotteryType, [1, 2, 3, 4, 5, 6, 7])) {
            throw new CustomException(['message'=>'彩种不正确']);
        }
        $year = date('Y');
        // 比较 redis 和 mysql 期数是否一致
        $redisNextIssue = Redis::get('lottery_real_open_issue_'.$lotteryType) ?? '002';
        $mysqlIssue = DB::table('history_numbers')
            ->where('year', $year)
            ->where('lotteryType', $lotteryType)
            ->latest('issue')
            ->value('issue');
        if ($redisNextIssue != $mysqlIssue+1) {
            Redis::expire('reopen_account_adminId_'.$lotteryType, 0);
            throw new CustomException(['message'=>'缓存和数据不一致，请联系开发确认']);
        }
        /**
         * 重开开始
         *  重开期数：$mysqlIssue
         */
        $userBets = DB::table('user_bets')
            ->lockForUpdate()
            ->where('lotteryType', $lotteryType)
            ->where('year', $year)
            ->where('issue', (int)$mysqlIssue)
            ->select(['id', 'user_id', 'each_bet_money', 'odd', 'win_money', 'win_status', 'status'])
//            ->whereNotIn('status', [0, -2]) // 全部查出来 好锁表
            ->orderBy('id')
            ->get()
            ->map(function($item) {
                return (array) $item;
            })
            ->toArray();
        if(!$userBets) {
            Redis::expire('reopen_account_adminId_'.$lotteryType, 0);
            throw new CustomException(['message'=>'暂无需重开的投注记录']);
        }
        $ttl = Redis::ttl('reopen_account_adminId_'.$lotteryType);
        if ( $ttl > 0) {
            return $this->apiSuccess('异步队列已包含此任务，请于'.$ttl.'秒后，重新投递任务');
        }

        event(new ReopenAccount($userBets, $mysqlIssue, $year, $lotteryType));

        return $this->apiSuccess('异步队列任务投递成功，请耐心等待');
    }

    /**
     * 修改资金入账方式
     * @param array $params
     * @return JsonResponse
     */
    public function bet_type_update(array $params): JsonResponse
    {
        $type = $params['type'];
        Redis::set('forecast_bet_win_type', $type);
        DB::table('auth_activity_configs')->where('k', 'forecast_bet_win_type')->update(['v'=>$type]);

        return $this->apiSuccess();
    }

    /**
     * 收益申请列表
     * @param array $params
     * @return JsonResponse
     */
    public function user_income_apply_list(array $params): JsonResponse
    {
        $account_name = $data['account_name'] ?? '';
        $userIds = [];
        if ($account_name) {
            $userIds = User::query()
                ->where('account_name', 'like', '%'.$account_name.'%')
                ->get(['id'])->toArray();
        }
        $res = IncomeApply::query()
            ->latest()
            ->when($userIds, function($query) use ($userIds) {
                $query->whereIn('user_id', $userIds);
            })
            ->when($params['status'] != -2, function($query) use ($params) {
                $query->where('status', $params['status']);
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'account_name', 'nickname']);
            }])
            ->paginate($params['limit'])->toArray();

        return $this->apiSuccess('',[
            'list'          => $res['data'],
            'total'         => $res['total'],
        ]);
    }

    /**
     * 收益更新
     * @param array $params
     * @return JsonResponse|null
     */
    public function income_apply_update_status(array $params): ?JsonResponse
    {
        return $this->commonUpdate(IncomeApply::query(), $params['id'], ['status'=>$params['status']]);
    }

    /**
     * 收益删除
     * @param array $params
     * @return JsonResponse|null
     */
    public function income_apply_delete(array $params): ?JsonResponse
    {
        return $this->commonDestroy(IncomeApply::query(), (array)$params['id']);
    }

    public function user_quota_list(array $params): JsonResponse
    {
        $userIds = [];
        $dateRange = $params['dateRange'] ?? [];
        if (!empty($params['account_name'])) {
            $userIds = User::query()
                ->where('account_name', 'like', '%'.$params['account_name'].'%')
                ->get(['id'])->toArray();
            if (!$userIds) {
                return $this->apiSucess('', []);
            }
        }
        $list = UserPlatRechargeDate::query()
        ->when($userIds, function($query) use ($userIds) {
            $query->whereIn('user_id', $userIds);
        })
        ->when($dateRange, function($query) use ($dateRange) {
            $query->whereBetween('created_at', $dateRange);
        })
        ->with(['user'=>function($query) {
            $query->select(['id', 'account_name', 'nickname']);
        }, 'plat'=>function($query) {
            $query->select(['id', 'name']);
        }])
        ->where('money', '<>', '0.00')
        ->orderBy('created_at', 'desc')
        ->paginate($params['limit'])->toArray();

        $platforms = Platform::query()->latest()->selectRaw('id as value, name as label')->get();
        return $this->apiSuccess('', [
            'list'         => $list['data'],
            'total'        => $list['total'],
            'plat_id'    => $platforms,
        ]); 
    }
}
