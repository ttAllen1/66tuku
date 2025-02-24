<?php

namespace Modules\Api\Services\platform;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\Activite;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\Platform;
use Modules\Api\Models\SmsSend;
use Modules\Api\Models\User;
use Modules\Api\Models\UserPlatform;
use Modules\Api\Models\UserPlatRecharge;
use Modules\Api\Models\UserPlatWithdraw;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Exceptions\StatusData;
use Modules\Common\Models\UserPlatQuota;
use Modules\Common\Models\UserPlatRechargeDate;

class PlatformService extends BaseApiService
{
    protected $_secret = 'cea1ff3rf72^&HU7JdFwe232(*&^';
    protected $_picture_bed_url = 'https://tu.118tapi.com';
    private $_verify_funds_attempts = 5;
    /**
     * 平台列表
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(): JsonResponse
    {
        $plats = Platform::query()
            ->where('status', 1)
            ->select(['id', 'name', 'website', 'status'])
            ->orderBy('sort')->get();
        if ($plats->isEmpty()) {
            throw new CustomException(['message'=>ApiMsgData::DATA_NOT_FOUND]);
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $plats->toArray());
    }

    /**
     * 用户平台列表
     * @return JsonResponse
     * @throws CustomException
     */
    public function user_plat(): JsonResponse
    {
        try{
            DB::beginTransaction();
            $user_id = auth('user')->id();
            $userPlats = UserPlatform::query()
                ->where('user_id', $user_id)
                ->where('status', 1)
                ->whereHas('plats', function($query) {
                    $query->where('status', 1)->select(['id', 'name']);
                })
                ->with(['plats'=>function($query) {
                    $query->select(['id', 'name']);
                }])
                ->latest()->get();
            if ($userPlats->isEmpty()) {
                throw new CustomException(['message'=>'未绑定彩票平台']);
            }
            $userPlats = $userPlats->toArray();
            // 获取充值倍数
            $incr_withdraw_limit_multiple = AuthActivityConfig::query()->where('k', 'incr_withdraw_limit_multiple')->value('v');
            // 先去图床平台查询
            foreach($userPlats as $k => $v) {
                $data['plat_name'] = $v['plats']['name'];
                $data['plat_user_name'] = trim($v['plat_user_name']);
                $data['plat_user_account'] = trim($v['plat_user_account']);
                $data['plat_id'] = $v['plat_id'];
                $this->queryPicturePlat($data);
            }
            foreach($userPlats as $k => $v) {
                $res = $this->checkRechargeDate($v['plat_id'], $v['plat_user_account'], $user_id);
                if ($res) { // 给用户加提现额度
                    DB::table('users')
                        ->where('id', $user_id)
                        ->update([
                            'withdraw_lave_limit' => DB::raw('withdraw_lave_limit + ' . $incr_withdraw_limit_multiple * $res),
                            'withdraw_limit' => DB::raw('withdraw_limit + ' . $incr_withdraw_limit_multiple * $res)
                        ]);
                    $date = date('Y-m-d H:i:s');
                    DB::table('user_plat_recharge_dates')->insert([
                        'user_id'       => $user_id,
                        'plat_id'       => $v['plat_id'],
                        'money'         => $res,
                        'created_at'    => $date,
                        'updated_at'    => $date,
                    ]);
                    // 入库
                    $existingQuota = UserPlatQuota::query()->where([
                        'user_id' => $user_id,
                        'plat_id' => $v['plat_id'],
                    ])->first();
                    if ($existingQuota) {
                        // 如果找到记录，更新 `quota` 为当前 `quota` 加上 `$res['data']['money']`
                        $existingQuota->update([
                            'quota' => $existingQuota->quota + $res,
                            'updated_at' => $date,
                        ]);
                    } else {
                        // 如果未找到记录，创建新记录并设置 `quota` 为 `$res['data']['money']`
                        UserPlatQuota::query()->create([
                            'user_id' => $user_id,
                            'plat_id' => $v['plat_id'],
                            'quota' => $res,
                            'created_at' => $date,
                        ]);
                    }
                }
                $userPlats[$k]['plat_withdraw_limit'] = UserPlatQuota::query()->where('user_id', $user_id)->where('plat_id', $v['plat_id'])->value('quota') ?: '0.00';
            }
//            $userPlats

            DB::commit();
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $userPlats);
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error("用户提现失败123：", ['message'=>$exception->getMessage()]);
            if ($exception instanceof CustomException) {
                throw new CustomException(['message'=>$exception->getMessage()]);
            }
            throw new CustomException(['message'=>'提现失败，请联系管理员']);
        }
    }

    /**
     * 平台绑定
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function bind($params): JsonResponse
    {
        try{
            $user_id = auth('user')->id();
            $this->checkBind($params['plat_id'], $params['plat_user_account'], $params['plat_user_name']);
            UserPlatform::query()->create([
                'plat_id'           => $params['plat_id'],
                'user_id'           => $user_id,
                'plat_user_name'    => $params['plat_user_name'],
                'plat_user_account' => $params['plat_user_account'],
                'status'            => $this->getCheckStatus(13) == 1 ? 0 : 1
            ]);
            // 去49图库查看是否有该账号的记录
            $data['plat_name'] = DB::table('platforms')->where('id', $params['plat_id'])->value('name');
            $data['plat_user_name'] = trim($params['plat_user_name']);
            $data['plat_user_account'] = trim($params['plat_user_account']);
            $data['plat_id'] = $params['plat_id'];
            $this->queryPicturePlat($data);
            // 五福统计
            $this->fiveActivity($user_id);
            return $this->apiSuccess(ApiMsgData::BIND_API_SUCCESS);
        } catch (CustomException $exception) {

            throw new CustomException(['message'=>$exception->getMessage()]);
        } catch (\Exception $exception) {
            Log::error('会员绑定平台失败：', ['message'=>$exception->getMessage()]);

            throw new CustomException(['message'=>ApiMsgData::BIND_API_ERROR]);
        }
    }

    public function queryPicturePlat($data)
    {
        try{
            $platName = trim($data['plat_name']);
            $platUserName = trim($data['plat_user_name']);
            $platUserAccount = trim($data['plat_user_account']);
            $token = md5($this->_secret.'#'.$platName.'#'.$platUserName.'#'.$platUserAccount);
            $response = Http::withOptions([
                'verify'=>false
            ])->timeout(5)->retry(3, 100)
                ->get(sprintf($this->_picture_bed_url.'/api/v1/three/queryIDByPlatAccount?plat_name=%s&plat_user_name=%s&plat_user_account=%s&token=%s', $platName, $platUserName, $platUserAccount, $token));
            if ($response->status() != 200) {
                throw new CustomException(['message'=>'与图库平台通信中断']);
            }
        }catch (\Exception $exception) {
            throw new CustomException(['message'=>'与图库平台通信中断:'.$exception]);
        }
        $platId = $data['plat_id'];
        $userId = auth('user')->id();
        if ($response->body()) {
            // 入库
            $res = json_decode($response->body(), true);
            if (UserPlatRechargeDate::query()
                ->where('user_id', $userId)
                ->where('plat_id', $platId)
                ->where('created_at', $res['data']['created_at'])
                ->exists()) {
                UserPlatRechargeDate::query()
                    ->where('user_id', $userId)
                    ->where('plat_id', $platId)
                    ->where('created_at', $res['data']['created_at'])
                    ->update([
                        'money' => $res['data']['money'],
                        'updated_at' => now()->format('Y-m-d H:i:s')
                    ]);
            } else {
                UserPlatRechargeDate::query()
                    ->insert([
                        'user_id' => $userId,
                        'plat_id' => $platId,
                        'created_at' => $res['data']['created_at'], // 条件
                        'money' => $res['data']['money'],
                        'updated_at' => now()->format('Y-m-d H:i:s')
                    ]);
//                $existingQuota = UserPlatQuota::query()->where([
//                    'user_id' => $userId,
//                    'plat_id' => $platId,
//                ])->first();
//                if ($existingQuota) {
//                    $existingQuota->update([
//                        'quota' => $existingQuota->quota + $res['data']['money'],
//                        'updated_at' => $res['data']['created_at'],
//                    ]);
//                } else {
//                    // 如果未找到记录，创建新记录并设置 `quota` 为 `$res['data']['money']`
//                    UserPlatQuota::query()->create([
//                        'user_id' => $userId,
//                        'plat_id' => $platId,
//                        'quota' => $res['data']['money'],
//                        'created_at' => $res['data']['created_at'],
//                    ]);
//                }
            }
//            UserPlatRechargeDate::query()->firstOrCreate([
//                'user_id' => $userId,
//                'plat_id' => $platId,
//                'created_at' => $res['data']['created_at'] // 条件
//            ], [
//                'money' => $res['data']['money'],
//                'updated_at' => now()->format('Y-m-d H:i:s')
//            ]);
            // 入库


        }
    }

    /**
     * 额度列表
     * @return JsonResponse
     */
    public function quotas(): JsonResponse
    {
        $quota_list = (new ConfigService())->getConfigs(['quota_list']);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $quota_list['quota_list']);
    }

    /**
     * 会员充值
     * @param $params
     * @return JsonResponse|mixed
     * @throws CustomException
     */
    public function recharge($params)
    {
        try{
            $quota = $params['quota'];
            $userPlatId = $params['user_plat_id'];
            $isSystem = $params['is_system'] ?? false;
            $userPlatInfo = UserPlatform::query()->where('status', 1)->select(['plat_id', 'plat_user_name'])->findOrFail($userPlatId);
            $final_money = $quota;
            if (!$isSystem) {
                $recharge_config = (new ConfigService())->getConfigs(['recharge_open', 'recharge_rate', 'found_send_sms']);
                $userMobile = User::query()->where('id', auth('user')->id())->value('mobile');
                if ($recharge_config['found_send_sms']==1 && !$userMobile) {
                    throw new CustomException(['message'=>'请先绑定手机号', "status"=>StatusData::MOBILE_MUST_BIND]);
                }
                $this->checkRecharge($recharge_config);
                $rate = 0;
                if ($recharge_config['recharge_rate']) {
                    $rate = ($quota*$recharge_config['recharge_rate']/100);
                    $final_money = $quota - ($quota*$recharge_config['recharge_rate']/100);
                }
            } else {
                $rate = 0;
            }
            if ($isSystem) {
                $status = 0;
            } else {
//                $status = $this->getCheckStatus(14) == 1 ? 0 : 1;
                $status = 0;
            }
            $res = UserPlatRecharge::query()->create([
                'user_id'           => $isSystem ? $params['user_id'] : auth('user')->id(),
                'money'             => $quota,
                'rate'              => $rate,
                'final_money'       => $final_money,
                'trade_no'          => $this->setTradeNo(),
                'plat_id'           => $userPlatInfo['plat_id'],
                'is_revoke'         => $isSystem ? 1 : 0,
                'status'            => $status
            ]);
            if ($isSystem) {
                return $res['id'];
            }

            return $this->apiSuccess(ApiMsgData::RECHARGE_API_SUCCESS);
        }catch (\Exception $exception) {
            Log::error('会员充值失败：', ['message'=>$exception->getMessage()]);

            throw new CustomException(['message'=>$exception->getMessage(), "status"=>$exception->getCode()]);
        }
    }

    /**
     * 提现页面
     * @return JsonResponse
     * @throws CustomException
     */
    public function withdraw_page(): JsonResponse
    {
        $userId = auth('user')->id();
        $userInfo = User::query()
            ->where('id', $userId)
            ->select(['account_balance', 'fund_password', 'mobile', 'withdraw_limit', 'withdraw_lave_limit'])
            ->first();
        if (!$userInfo['fund_password']) {
            throw new CustomException(['message'=>'资金密码为空']);
        }
        $tyQuota = DB::table('user_plat_quotas')->where('user_id', $userId)->where('plat_id', 0)->value('quota');
        if ($tyQuota) {
            $userInfo['withdraw_lave_limit'] = $tyQuota;
        } else {
            DB::table('user_plat_quotas')
                ->insert([
                    'user_id' => $userId,
                    'plat_id' => 0,
                    'quota' => $userInfo['withdraw_limit'],
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ]);
        }
        $configs = (new ConfigService())->getConfigs(['withdraw_num', 'withdraw_min_each', 'withdraw_max_each', 'withdraw_max', 'withdraw_rate', 'found_send_sms']);
        if ($configs['found_send_sms']==1 && !$userInfo['mobile']) {
            throw new CustomException(['message'=>'请先绑定手机号', "status"=>StatusData::MOBILE_MUST_BIND]);
        }
        $configs['account_balance'] = $userInfo['account_balance'];
//        $withdrawMoney = UserPlatWithdraw::query()->where('user_id', auth('user')->id())->where('status', 0)->sum('money');

//        $withdrawMoney1 = UserPlatWithdraw::query()->where('user_id', auth('user')->id())->where('status', 1)->sum('money');
        $withdrawMoney2 = UserPlatWithdraw::query()
            ->where('user_id', $userId)
            ->where('status', 0)
            ->sum('money');
        if ($userInfo['withdraw_lave_limit'] <= $withdrawMoney2){
            $configs['withdraw_limit'] = 0;
        } else {
            $configs['withdraw_limit'] = $userInfo['withdraw_lave_limit'] - $withdrawMoney2;
        }

        $incr_withdraw_limit_multiple = AuthActivityConfig::query()->where('k', 'incr_withdraw_limit_multiple')->value('v');
        $configs['incr_withdraw_limit_multiple'] = $incr_withdraw_limit_multiple ?? 2;

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $configs->toArray());
    }

    /**
     * 会员提现
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function withdraw($params): JsonResponse
    {
//        $userId = auth('user')->id();
//        if ($userId!=432) {
//            throw new CustomException(['message'=>'系统提现系统升级中，预计40分钟']);
//        }

        try {
            DB::beginTransaction();
            // 查询活跃记录
            $userId = auth('user')->id();
            $activityDate = Activite::query()->where('user_id', $userId)->orderByDesc('created_at')->value('updated_at');
            if (!$activityDate) {
                throw new CustomException(['message'=>'网络错误，请重试']);
            }
            $threeDaysAgo = Carbon::today()->subDays(3);
            $someDate = Carbon::parse($activityDate);
            if($someDate->lessThanOrEqualTo($threeDaysAgo)) {
                throw new CustomException(['message'=>'网络错误，请重试']);
            }

            $quota = $params['quota'];
            $user_plat_id = $params['user_plat_id'];
            $fund_password = $params['fund_password'];
            $params['sms_code'] = $params['sms_code'] ?? '';
            $withdraw_config = (new ConfigService())->getConfigs(['withdraw_open', 'withdraw_rate', 'withdraw_num', 'withdraw_min_each', 'withdraw_max_each', 'withdraw_max', 'found_send_sms']);
            $userInfo = User::query()->where('id', $userId)->select(['id', 'mobile', 'register_at', 'withdrawal_num', 'withdrawal_total', 'withdraw_lave_limit', 'login_num', 'growth_score'])->first();
            if ( (strtotime($userInfo['register_at']) + 3600*24) > strtotime(date("Y-m-d H:i:s"))) {
                throw new CustomException(['message'=>'账号注册未满24小时，不支持提现']);
            }
            if ( $userInfo['login_num'] <= 3 ) {
                throw new CustomException(['message'=>'您的登录次数未达三次']);
            }
            if ( $userInfo['growth_score'] < 100 ) {
                throw new CustomException(['message'=>'成长值不够哦～']);
            }
            if ($withdraw_config['found_send_sms']==1 ) {
                if (!$userInfo['mobile']) {
                    throw new CustomException(['message'=>'请先绑定手机号', "status"=>StatusData::MOBILE_MUST_BIND]);
                }
                if (!$params['sms_code']) {
                    throw new CustomException(['message'=>'手机验证码必填']);
                }
                // 短信验证码是否过期
                $ttl = SmsSend::query()->where('mobile', $userInfo['mobile'])
                    ->where('code', $params['sms_code'])
                    ->where('scene', 'withdraw')
                    ->select(['ttl', 'created_at'])->first();
                if (!$ttl) {
                    return response()->json(['message' => '短信验证码不存在或已失效', 'status' => 40000], 400);
                }
                if ((strtotime($ttl['created_at']) + $ttl['ttl'] * 60) < time()) {
                    return response()->json(['message' => '短信验证码不存在或已失效', 'status' => 40000], 400);
                }
            }

            $rate = 0;
            $final_money = $quota;
            if ($withdraw_config['withdraw_rate']) {
                $rate = ($quota*$withdraw_config['withdraw_rate']/100);
                $final_money = $quota - ($quota*$withdraw_config['withdraw_rate']/100);
            }
            $userPlatInfo = UserPlatform::query()->where('status', 1)->select(['plat_id', 'plat_user_name', 'plat_user_account'])->findOrFail($user_plat_id);
            $this->checkWithdraw($quota, $withdraw_config, $fund_password, $userPlatInfo['plat_id'], $userId);
//            dd($userPlatInfo);
            $this->checkBind($userPlatInfo['plat_id'], $userPlatInfo['plat_user_account'], $userPlatInfo['plat_user_name'], false);

            UserPlatWithdraw::query()->create([
                'user_id'           => $userId,
                'money'             => $quota,
                'rate'              => $rate,
                'final_money'        => $final_money,
                'trade_no'          => $this->setTradeNo(),
                'plat_id'           => $userPlatInfo['plat_id'],
                'nums'              => $userInfo['withdrawal_num'] ?? 0,
                'status'            => 0 // 后台去审核 才到账彩票平台
            ]);
            // 立即减少图库余额
            User::query()->where('id', $userId)->decrement('account_balance', $quota);
            if ($userInfo['withdrawal_total']=='0.00') {
                $withdrawMoneys = DB::table('user_plat_withdraws')
                    ->where('user_id', $userId)
                    ->whereIn('status', [0, 1])->sum('money');
                User::query()->where('id', $userId)->increment('withdrawal_total', $quota+$withdrawMoneys);
            } else {
                User::query()->where('id', $userId)->increment('withdrawal_total', $quota);
            }
//            User::query()->where('id', auth('user')->id())->decrement('withdraw_lave_limit', $quota);

//            User::query()->where('id', auth('user')->id())->increment('withdrawal_num');
            DB::commit();
            return $this->apiSuccess(ApiMsgData::WITHDRAW_API_SUCCESS);
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error('会员提现失败：', ['message'=>$exception->getMessage()]);
            if ( $exception instanceof ModelNotFoundException ) {
                throw new CustomException(['message'=>'平台不存在或被禁用']);
            } else {
                throw new CustomException(['message'=>$exception->getMessage(), "status"=>$exception->getCode()]);
            }
        }
    }

    /**
     * 检查绑定信息是否正确
     * @param $plat_id
     * @param $plat_user_account
     * @param $plat_user_name
     * @param bool $isCheckExist
     * @return void
     * @throws CustomException
     */
    private function checkBind($plat_id, $plat_user_account, $plat_user_name, bool $isCheckExist=true): void
    {
//        dd(strtolower(md5('CheckUserExist#'.$plat_user_account.'#'.$plat_user_name.'#9f196b632c7024061567b50dfd6983eb')));
        try{
            if ($isCheckExist) {
                $isExists = UserPlatform::query()
                    ->where('plat_id', $plat_id)
                    ->where('status', '<>', -1)
                    ->where('user_id', auth('user')->id())
                    ->exists();
                if ($isExists) {
                    throw new CustomException(['message'=>'您在此平台已经绑定过了']);
                }
                $isExists = UserPlatform::query()
                    ->where('plat_id', $plat_id)
                    ->where('status', '<>', -1)
//                    ->where('plat_user_name', $plat_user_name)
                    ->where('plat_user_account', $plat_user_account)
                    ->exists();
                if ($isExists) {
                    throw new CustomException(['message'=>'此平台该账号已被绑定']);
                }
            }

            $query_user_api_obj = Platform::query()->where('id', $plat_id)->select(['query_user_api', 'token'])->first();
            $query_user_api = $query_user_api_obj['query_user_api'];
            $token = trim($query_user_api_obj['token']);
//            dd($token);
//            CheckUserExist#UserName#RealName#Key
            $sign = strtolower(md5('CheckUserExist#'.$plat_user_account.'#'.$plat_user_name.'#'.$token));
            $response = Http::withOptions([
                'verify'=>false
            ])->timeout(5)->retry(3, 100)->get(sprintf($query_user_api, $plat_user_account, $sign, $plat_user_name));
            if ($response->status() != 200) {
                throw new CustomException(['message'=>'与彩票平台通信中断']);
            }
            $res = json_decode($response->body(), true);
            if ( $res['Status'] != 0 ) {
                if (!$isCheckExist) {
                    throw new CustomException(['message'=>'与平台真实名不符，请联系管理员修改']);
                } else {
                    throw new CustomException(['message'=>'平台：'.$res['Message']]);
                }
            }
            return;
        } catch (ConnectionException $exception) {
            throw new CustomException(['message'=>'与彩票平台通信超时']);
        } catch (RequestException $exception) {
            throw new CustomException(['message'=>'与彩票平台尝试通信失败']);
        }
    }

    /**
     * 检测充值 并 充值
     * @param $recharge_config
     * @return void
     * @throws CustomException
     */
    private function checkRecharge($recharge_config)
    {
        if (!$recharge_config['recharge_open']) {
            throw new CustomException(['message'=>'平台充值暂时停用']);
        }
        return ;
    }

    /**
     * 检测提现 并 提现
     * @param $quota
     * @param $withdraw_config
     * @param string $fund_password
     * @param $plat_id
     * @return void
     * @throws CustomException
     */
    private function checkWithdraw($quota, $withdraw_config, string $fund_password, $plat_id, $userId)
    {
        try{
            if (!$withdraw_config['withdraw_open']) {
                throw new CustomException(['message'=>'平台提现暂时停用']);
            }
            if (!$fund_password) {
                throw new CustomException(['message'=>'请先设置资金密码']);
            }
            $userInfo = User::query()
                ->where('id', auth('user')->id())
                ->lockForUpdate()
                ->firstOrFail(['id', 'fund_password', 'is_lock_fund_password', 'account_balance', 'is_balance_freeze', 'withdraw_limit', 'withdraw_lave_limit', 'status']);
            if ($userInfo['is_balance_freeze'] == 1) {
                throw new CustomException(['message'=>'您的资金已被冻结，请联系客服']);
            }
            if ($userInfo['status'] == 2) {
                throw new CustomException(['message'=>'账号异常，请联系客服']);
            }
            if ($userInfo['is_lock_fund_password']) {
                throw new CustomException(['message'=>'您的资金密码已被锁定，请联系客服']);
            }
            if ($userInfo['account_balance'] < $quota) {
                throw new CustomException(['message'=>'您的资金余额不足']);
            }
            $totalWithdrawMoney = UserPlatWithdraw::query()
                ->where('user_id', $userId)
                ->where('status', 0)
                ->where('plat_id', $plat_id)
                ->lockForUpdate()
                ->sum('money');
            $laveLimit = DB::table('user_plat_quotas')->where('user_id', $userId)->whereIn('plat_id', [0, $plat_id])->sum('quota');
            if (($totalWithdrawMoney+$quota)>$laveLimit) {
                $platName = DB::table('platforms')->where('id', $plat_id)->value('name');
                throw new CustomException(['message'=>'您的'.$platName.'平台提现额度已用完，请尝试其他平台提现']);
            }

            if (!Hash::check($this->_fund_password_salt.$fund_password, $userInfo['fund_password'])) {
                $redisKey = 'verify_funds_attempts:' . auth('user')->id(). ':' . date('Y-m-d');
                Redis::setnx($redisKey, 0);
                Redis::incr($redisKey);
                $attempts = Redis::get($redisKey);
                if ($attempts>=$this->_verify_funds_attempts) {
                    $userInfo->update(['is_lock_fund_password'=>1]);
                    DB::commit();
                    throw new CustomException(['message'=>'今日失败次数过多，资金密码被锁定']);
                }
                throw new CustomException(['message'=>'资金密码不正确，今日还可尝试'.($this->_verify_funds_attempts-$attempts).'次']);
            }

            if ((int)$withdraw_config['withdraw_max_each'] && $quota>$withdraw_config['withdraw_max_each']) {
                throw new CustomException(['message'=>'单次提现超过最大值']);
            }
            if ((int)$withdraw_config['withdraw_min_each'] && $quota<$withdraw_config['withdraw_min_each']) {
                throw new CustomException(['message'=>'单次提现少于最小值']);
            }
            $userWithdraws = UserPlatWithdraw::query()
                ->where('user_id', auth('user')->id())
                ->whereDate('created_at', date('Y-m-d'))
                ->whereIn('status', [0, 1])->get();
            if ( (int)$withdraw_config['withdraw_num'] && count($userWithdraws)>=$withdraw_config['withdraw_num'] ) {
                if (auth('user')->id() != 75454) {
                    throw new CustomException(['message'=>'今日提现次数已用完']);
                }
            }
            if ( (int)$withdraw_config['withdraw_max'] && $userWithdraws->sum('money')>=$withdraw_config['withdraw_max']) {
                throw new CustomException(['message'=>'今日提现已达最大值']);
            }

            return;
        } catch (ConnectionException $exception) {
            throw new CustomException(['message'=>'与彩票平台通信超时']);
        } catch (RequestException $exception) {
            throw new CustomException(['message'=>'与彩票平台尝试通信失败']);
        }
    }

    /**
     * 查询用户是否在规定的时间是否有充值
     * @param $plat_id
     * @param $plat_user_account
     * @param $user_id
     * @return int|float
     * @throws CustomException
     */
    public function checkRechargeDate($plat_id, $plat_user_account, $user_id)
    {
        try{
            $StartBindDate = DB::table('user_platforms')->where('user_id', $user_id)->where('plat_id', $plat_id)->value('created_at');

            $query_user_api_obj = Platform::query()->where('id', $plat_id)->select(['query_user_recharge_api', 'token'])->first();
            $query_user_recharge_api = $query_user_api_obj['query_user_recharge_api'];
            $token = trim($query_user_api_obj['token']);
            $StartDate = DB::table('user_plat_recharge_dates')
                ->where('user_id', $user_id)
                ->orderByDesc('created_at')
                ->value('created_at');
            if (!$StartDate) {
                // 用户绑定该平台的时间
                $StartDate = $StartBindDate ?: "2023-12-01 00:00:00";
            }
            $EndDate = date('Y-m-d H:i:s');
            $sign = strtolower(md5("UserRectotal#".$plat_user_account.'#'.$StartDate.'#'.$EndDate.'#'.$token));
            $response = Http::withOptions([
                'verify'=>false
            ])->timeout(5)->retry(3, 100)->get(sprintf($query_user_recharge_api, $plat_user_account, $sign, $StartDate, $EndDate));
            if ($response->status() != 200) {
                throw new CustomException(['message'=>'与彩票平台通信中断']);
            }
            $res = json_decode($response->body(), true);
            if ($res['Status']==0 && $res['RecTotal']>0) { // 查询成功有充值金额
                return $res['RecTotal'];
            }
            return 0;
        } catch (ConnectionException $exception) {
            throw new CustomException(['message'=>'与彩票平台通信超时']);
        } catch (RequestException $exception) {
            throw new CustomException(['message'=>'与彩票平台尝试通信失败']);
        } catch (\Exception $exception) {
            Log::error("6768失败", ['message' => $exception->getMessage() . "行号：" . $exception->getLine()]);
            throw new CustomException(['message'=>'获取失败']);
        }
    }
}
