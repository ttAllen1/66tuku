<?php

namespace Modules\Api\Services\auth;

use Carbon\Carbon;
use Faker\Factory;
use Gai871013\IpLocation\Facades\IpLocation;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Admin\Models\WebConfig;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\AuthConfig;
use Modules\Api\Models\Invitation;
use Modules\Api\Models\SmsSend;
use Modules\Api\Models\User;
use Modules\Api\Models\UserLogin;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class LoginService extends BaseApiService
{
    /**
     * 用户登录
     * @param array $data
     * @return JsonResponse|void
     * @throws ApiException
     * @throws CustomException
     */
    public function login(array $data)
    {
        $configs = (new ConfigService())->getConfigs(['login_send_sms', 'app_first_gift', 'wydun']);
        if ($configs['wydun']) {
            if (!isset($data['NECaptchaValidate']))
            return response()->json(['message' => '行为验证码必填', 'status' => 40000], 400);
        } else {
            if (!isset($data['captcha']) || !isset($data['key'])) {
                return response()->json(['message' => '图形验证码必填', 'status' => 40000], 400);
            }
        }
        $device_code = $data['device_code'] ?? '';
        $device = $data['device'] ?? 'h5';
        unset($data['device_code']);
        unset($data['captcha']);
        unset($data['device']);
        unset($data['key']);
        unset($data['NECaptchaValidate']);
        if (auth('user')->attempt($data)) {
            $userInfo = User::getUserInfoByAccountName($data['account_name']);
            if ($userInfo) {
                if ($userInfo['status'] != 1 || $userInfo['is_lock'] != 2) {
                    throw new CustomException(['message' => ApiMsgData::ACCOUNT_DISABLE_API_SUCCESS]);
                }
                $user_info = $userInfo->toArray();
                $user_info['password'] = $data['password'];
                $token = (new TokenService)->setToken($user_info);
                // 获取用户IP
                $ip = $this->getIp();
                // 获取用户IP归属地
                $ipCountry = '';
                if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                    $ipAddress = IpLocation::getLocation($ip);
                    $ipCountry = $ipAddress['country'] ?? '';
                }

                $updateData = [
                    'last_login_at'          => date('Y-m-d H:i:s'),
                    'last_login_ip'          => filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0,
                    'last_login_area'        => $ipCountry,
                    'last_login_device'      => self::deviceType(),
                    'last_login_device_code' => $device_code,
                    'is_online'         => 1,
                    'chat_pwd'          => md5($data['password']),
                    'chat_user'         => $userInfo['chat_user'] ?? $this->str_rand(7)
                ];
                User::query()->where(['id' => $user_info['id']])->increment('login_num', 1, $updateData);
                UserLogin::query()->insert([
                   'user_id'            => $user_info['id'],
                   'login_ip'           => $updateData['last_login_ip'],
                   'login_area'         => $ipCountry,
                   'login_device'       => $updateData['last_login_device'],
                   'login_device_code'  => $device_code,
                   'created_at'         => $updateData['last_login_at']
                ]);
                if (!DB::table('user_plat_quotas')
                    ->where('user_id', $user_info['id'])
                    ->where('plat_id', 0)
                    ->exists()) {
                    DB::table('user_plat_quotas')->insert([
                        'user_id'       => $user_info['id'],
                        'plat_id'       => 0,
                        'quota'         => 100,
                        'created_at'    => date('Y-m-d H:i:s')
                    ]);
                }
                // 是否携带app信息
                if ($configs['app_first_gift'] && $device_code) {
                    (new BaseApiService())->appWelfare(2, $device_code, $user_info['id'], $device, "首次使用APP账户登录", "APP登录得彩金", "恭喜您使用APP在49图库首次使用账号登录成功，49图库已将使用APP登录福利发送至您的福利中心，七日内有效哦～49图库祝您生活愉快！");
                }
                /** @noinspection PhpExpressionResultUnusedInspection */
                $this->fiveActivity($user_info['id']);
                return $this->apiSuccess(ApiMsgData::LOGIN_API_SUCCESS, $token);
            }
        }
        $this->apiError(ApiMsgData::LOGIN_API_ERROR);
    }

    /**
     * 获取设备类型
     * @return int
     */
    private function deviceType(): int
    {
        // 获取登陆设备
        switch (request()->input('device')) {
            case 'ios':
                $device = 2;
                break;
            case 'android':
                $device = 3;
                break;
            case 'h5':
                $device = 4;
                break;
            default:
                $device = 1;
                break;
        }
        return $device;
    }

    /**
     * 获取设备类型
     * @return int
     */
    private function deviceType2(): int
    {
        // 获取登陆设备
        switch (request()->input('device')) {
            case 'ios':
                $device = 2;
                break;
            case 'h5':
                $device = 4;
                break;
            case 'android':
            default:
                $device = 3;
                break;
        }
        return $device;
    }

    /**
     * 随机字符串
     * @param int $length 长度
     * @return string
     */
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

    public function str_rand__chinese(int $length): string
    {
        return Factory::create('zh_CN')->name().Factory::create('zh_CN')->name();
    }

    /**
     * 三方站点登录
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function login_3($params): JsonResponse
    {
        try {
            $web_config = WebConfig::query()->where('token', $params['token'])->where('status', 1)->firstOrFail([
                'id', 'web_sign', 'avatar_prefix_url'
            ]);
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '站点不存在，或已关闭登录']);
        }
        $params['user_name'] = $params['user_name'] ?? 'tk_' . $params['user_id'];
        $account_name = $params['user_name'] . '_' . $web_config['web_sign'];
        $pwdInit = 'abc123';
        if (User::query()->where('web_id', $web_config['id'])->where('account_name', $account_name)->value('id')) {
            $token = (new TokenService())->setToken(['account_name' => $account_name, 'password' => $pwdInit]);
            return $this->apiSuccess(ApiMsgData::LOGIN_API_SUCCESS, $token);
        }
        $userData = [
            'account_name'      => $account_name,
            'web_user_id'       => $params['user_id'],
            'web_id'            => $web_config['id'],
            'web_sign'          => $web_config['web_sign'],
            'nickname'          => $params['nick_name'],
            'chat_user'         => $this->str_rand(7),
            'password'          => bcrypt($pwdInit),
            'chat_pwd'          => md5($pwdInit),
            'invite_code'       => $this->randString(),
            'avatar'            => $web_config['avatar_prefix_url'] . $params['avatar'],
            'new_avatar'        => $web_config['avatar_prefix_url'] . $params['avatar'],
            'register_ip'       => 0,
            'register_area'     => '',
            'register_at'       => date('Y-m-d H:i:s'),
            'created_at'        => date('Y-m-d H:i:s'),
            'last_login_at'     => date('Y-m-d H:i:s'),
            'last_login_ip'     => 0,
            'last_login_area'   => '',
            'last_login_device' => 1,
            'is_online'         => 1,
            'avatar_is_check'   => 1,
        ];
        $userId = User::query()->insertGetId($userData);
        if (!$userId) {
            throw new CustomException(['message' => ApiMsgData::REGISTER_API_ERROR]);
        }

        $token = (new TokenService())->setToken(['account_name' => $account_name, 'password' => $pwdInit]);

        return $this->apiSuccess(ApiMsgData::REGISTER_API_SUCCESS, $token);
    }

    /**
     * 密码找回
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function forget(array $data): JsonResponse
    {
        if (User::updateUserPassword($data)) {
            return $this->apiSuccess(ApiMsgData::UPDATE_API_SUCCESS);
        }
        throw new CustomException(['message' => ApiMsgData::UPDATE_API_SUCCESS]);
    }

    /**
     * 用户注册
     * @param array $data
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function register(array $data): JsonResponse
    {
//        throw new ApiException(['status'=>40009,'message'=>'网站升级维护中，请稍后访问']);
        try{
            DB::beginTransaction();
            $device_code = $data['device_code'] ?? '';
            $device = $data['device'] ?? 'h5';
            $configs = (new ConfigService())->getConfigs(['register_send_sms', 'register_gift', 'app_first_gift', 'wydun', 'name']);
            if ($configs['register_send_sms'] == 0) {
                if ($configs['wydun']) {
                    if (!isset($data['NECaptchaValidate'])) {
                        return response()->json(['message' => '行为验证码必填', 'status' => 40000], 400);
                    }
                } else {
                    if (!isset($data['captcha'])) {
                        return response()->json(['message' => '图形验证码必填', 'status' => 40000], 400);
                    }
                }
            }
            // 存在这个传参则是大于三次注册的
            if (isset($data['NECaptchaValidates'])) {
                return response()->json(['message' => '手机号已被拉黑，如有疑问请联系客服', 'status' => 40000], 400);
            }
            $mobileExists = User::query()->where('account_name', $data['account_name'])->lockForUpdate()->value('id');
            if ($mobileExists) {
                throw new CustomException(['message'=>'该账号已存在']);
            }
            if ($configs['register_send_sms'] == 1) {
                if (!isset($data['mobile']) || !isset($data['sms_code'])) {
                    return response()->json(['message' => '手机号和验证码必填', 'status' => 40000], 400);
                }
                $mobileExists = User::query()->where('mobile', $data['mobile'])->lockForUpdate()->value('id');
                if ($mobileExists) {
                    throw new CustomException(['message'=>'该手机号已存在']);
                }
                $ttl = SmsSend::query()->where('mobile', $data['mobile'])
                    ->where('code', $data['sms_code'])
                    ->where('scene', 'register')
                    ->select(['ttl', 'created_at'])->first();
                if (!$ttl) {
                    DB::rollBack();
                    return response()->json(['message' => '短信验证码不存在或已失效', 'status' => 40000], 400);
                }
                if ((strtotime($ttl['created_at']) + $ttl['ttl'] * 60) < time()) {
                    DB::rollBack();
                    return response()->json(['message' => '短信验证码不存在或已失效', 'status' => 40000], 400);
                }
            }

            $not_allow_str = ['微信', '威信', 'weixin', 'qq', '薇信', '管理员', '财务', '出纳', '系统', 'admin'];
            if (Str::contains($data['account_name'], $not_allow_str)) {
                DB::rollBack();
                return response()->json(['message' => '账号中存在违规字符', 'status' => 40000], 400);
            }
            $ip = $this->getIp();
            $searchIp = filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0;
            if ($searchIp && $searchIp != '759232143') {
                $counts = User::query()->where('register_ip', $searchIp)->count();
                if ($counts>3) {
                    DB::rollBack();
                    return response()->json(['message' => '该ip注册次数已达上限', 'status' => 40000], 400);
                }
            }
            // 获取用户IP归属地
            $ipCountry = '';
            if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                $ipAddress = IpLocation::getLocation($ip);
                $ipCountry = $ipAddress['country'] ?? '';
            }
            $userData = [
                'account_name'      => $data['account_name'],
                'mobile'            => ($configs['register_send_sms'] == 1 && $data['mobile']) ? $data['mobile'] : '',
                'nickname'          =>  $this->createNickName($configs['name']),
                'chat_user'         => $this->str_rand(7),
                'password'          => bcrypt($data['password']),
                'chat_pwd'          => md5($data['password']),
                'invite_code'       => $this->randString(),
                'avatar'            => AuthConfig::with('avatar')->first()->avatar->url,
                'new_avatar'        => AuthConfig::with('avatar')->first()->avatar->url,
                'register_ip'       => filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0,
                'register_area'     => $ipCountry,
                'register_at'       => date('Y-m-d H:i:s'),
                'last_login_at'     => date('Y-m-d H:i:s'),
                'last_login_ip'     => filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0,
                'last_login_area'   => $ipCountry,
                'last_login_device' => self::deviceType(),
                'is_online'         => 1,
                'avatar_is_check'   => 1,
                'register_device'   => $device
            ];
            $userId = User::insertGetId($userData);
            if (!$userId) {
                throw new CustomException(['message' => ApiMsgData::REGISTER_API_ERROR]);
            }
            // 平台额度
            DB::table('user_plat_quotas')->insert([
                'user_id'       => $userId,
                'plat_id'       => 0,
                'quota'         => 100,
                'created_at'    => date('Y-m-d H:i:s')
            ]);
            // 开启注册优惠 且 开启手机号注册验证
            if ($configs['register_gift'] && $configs['register_send_sms'] == 1) {
//            User::query()->where('id', $userId)->increment('account_balance', $register_gift_num, ['register_gift' =>1]);
                User::query()->where('id', $userId)->update(['register_gift' => 1]);
                $novice_register_species = AuthActivityConfig::query()->where('k', 'novice_register_species')->value('v');
                if (!is_numeric($novice_register_species)) {
                    $novice_register_species = json_decode($novice_register_species, true);
                    $novice_register_species = rand($novice_register_species[0], $novice_register_species[1]);
                }
                // 增加注册彩金
                $welfareData = [];
                $welfareData['user_id'] = [$userId];
                $welfareData['name'] = "平台注册（绑定）福利";
                $welfareData['is_random'] = 0;
                $welfareData['really_money'] = $novice_register_species;
                $welfareData['is_limit_time'] = 1;
                $welfareData['status'] = 0;
                $welfareData['is_send_msg'] = 1;
                $welfareData['msg']['title'] = '注册得彩金';
                $welfareData['msg']['content'] = "恭喜您在49图库注册账号成功，49图库已将彩金发送至您的福利中心，七日内有效哦～49图库祝您生活愉快！";
                $welfareData['valid_receive_date'] = [
                    Carbon::now()->toDateString(), Carbon::now()->addDays(6)->toDateString()
                ];
                (new UserWelfareService())->store($welfareData);

//            (new ActivityService())->modifyAccount($userId, 'register_gift', $register_gift_num);
            }
            // 是否携带app信息
            if ($configs['app_first_gift'] && $device_code) {
                (new BaseApiService())->appWelfare(1, $device_code, $userId, $device, "首次使用APP注册福利", "APP注册得彩金", "恭喜您使用APP在49图库注册账号成功，49图库已将使用APP注册福利发送至您的福利中心，七日内有效哦～49图库祝您生活愉快！");
            }
            if (!empty($data['invite_code'])) {
//                $this->addMoney($data['invite_code'], $userId);
//                $this->addScore($data['invite_code']);
                $invite_code = $data['invite_code'];
                $userInfo = User::query()->where('invite_code', strtoupper($invite_code))->select(['id'])->firstOrFail();
                // 一天只能邀请5人有福利
                if (Invitation::query()->where('user_id', $userInfo['id'])->count('id')<20) {
                    $counts = Invitation::query()
                        ->lockForUpdate()
                        ->where('user_id', $userInfo['id'])
                        ->whereDate('created_at', date('Y-m-d'))
                        ->count('id');
                    if ($counts<5) {
                        $welfareData = [];
                        $welfareData['user_id'] = [$userInfo['id']];
                        $welfareData['name'] = "平台拉新福利";
                        $welfareData['is_random'] = 0;
                        $welfareData['really_money'] = 4.99;
                        $welfareData['is_limit_time'] = 1;
                        $welfareData['status'] = 0;
                        $welfareData['is_send_msg'] = 1;
                        $welfareData['msg']['title'] = '邀请得彩金';
                        $welfareData['msg']['content'] = "恭喜您在49图库成功邀请一位新用户，49图库已将彩金发送至您的福利中心，七日内有效哦～49图库祝您生活愉快！";
                        $welfareData['valid_receive_date'] = [
                            \Carbon\Carbon::now()->toDateString(), \Illuminate\Support\Carbon::now()->addDays(6)->toDateString()
                        ];
                        (new UserWelfareService())->store($welfareData);
                    }
                }

                // 给被邀请人加【存入账户】下级
                $novice_invitation_species = AuthActivityConfig::query()->where('k', 'novice_invitation_species')->value('v');
                User::query()->where('id', $userId)->increment('account_balance', $novice_invitation_species, ['filling_gift'=>1]);
                (new ActivityService())->modifyAccount($userId, 'filling_gift', $novice_invitation_species);
                Invitation::query()->insert([
                    'user_id' => $userInfo['id'],
                    'to_userid' => $userId,
                    'level' => 1,
                    'money' => $novice_invitation_species
                ]);
                $this->addScore($invite_code);
            }
            $token = (new TokenService())->setToken([
                'account_name' => $data['account_name'], 'password' => $data['password']
            ]);
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error('用户注册失败：', ['message'=>$exception->getMessage()]);
            throw new CustomException(['message' => ApiMsgData::REGISTER_API_ERROR]);
        }
        DB::commit();
        return $this->apiSuccess(ApiMsgData::REGISTER_API_SUCCESS, $token);
    }

    /**
     * 校验手机号是否存在
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function mobile_exist($params): JsonResponse
    {
        if (!User::query()->where('mobile', $params['mobile'])->value('id')) {
            throw new CustomException(['message' => ApiMsgData::MOBILE_NOT_EXIST]);
        }

        return $this->apiSuccess();
    }

    /**
     * 手机号快捷登录
     * @param $data
     * @return JsonResponse|void
     * @throws ApiException
     * @throws CustomException
     */
    public function mobile_login($data)
    {
//        $configs = (new ConfigService())->getConfigs(['login_send_sms']);

//        if ($configs['login_send_sms'] ==1) {
//
//        }
        try {
            $device_code = $data['device_code'] ?? '';
            $device = $data['device'] ?? 'android';
            if (!isset($data['mobile']) || !isset($data['sms_code'])) {
                return response()->json(['message' => '手机号和验证码必填', 'status' => 40000], 400);
            }
            $ttl = SmsSend::query()->where('mobile', $data['mobile'])
                ->where('code', $data['sms_code'])
                ->where('scene', 'login')
                ->select(['ttl', 'created_at'])->first();
            if (!$ttl) {
                return response()->json(['message' => '短信验证码不存在或已失效', 'status' => 40000], 400);
            }
            if ((strtotime($ttl['created_at']) + $ttl['ttl'] * 60) < time()) {
                return response()->json(['message' => '短信验证码不存在或已失效', 'status' => 40000], 400);
            }

            $dataArr = User::query()->where('mobile', $data['mobile'])->select([
                'account_name', 'password'
            ])->firstOrFail();
            $dataArr->makeVisible('password');

            $data = $dataArr->toArray();

            $userInfo = User::query()->where('account_name', $data['account_name'])->first();
            if ($userInfo) {
                if ($userInfo['status'] != 1 || $userInfo['is_lock'] != 2) {
                    throw new CustomException(['message' => ApiMsgData::ACCOUNT_DISABLE_API_SUCCESS]);
                }

                $token = (new TokenService())->setToken2($userInfo);
                $user_info = $userInfo->toArray();
//                $user_info['password'] = $data['password'];

                // 获取用户IP
//                $ip = request()->ip();
                $ip = $this->getIp();
                // 获取用户IP归属地
                $ipCountry = '';
                if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                    $ipAddress = IpLocation::getLocation($ip);
                    $ipCountry = $ipAddress['country'] ?? '';
                }

                $updateData = [
                    'last_login_at'     => date('Y-m-d H:i:s'),
                    'last_login_ip'     => filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0,
                    'last_login_area'   => $ipCountry,
                    'last_login_device' => self::deviceType2(),
                    'last_login_device_code' => $device_code,
                    'is_online'         => 1,
                    'chat_pwd'          => md5($data['password']),
                    'chat_user'         => $userInfo['chat_user'] ?? $this->str_rand(7)
                ];
                User::where(['id' => $user_info['id']])->increment('login_num', 1, $updateData);
                UserLogin::query()->insert([
                    'user_id'            => $user_info['id'],
                    'login_ip'           => $updateData['last_login_ip'],
                    'login_area'         => $ipCountry,
                    'login_device'       => $updateData['last_login_device'],
                    'login_device_code'  => $device_code,
                    'created_at'         => $updateData['last_login_at']
                ]);
                $configs = (new ConfigService())->getConfigs(['app_first_gift']);
                // 是否携带app信息
//                $data['device_code'] = $data['device_code'] ?? '';
//                dd($configs['app_first_gift'], $device_code);
                if ($configs['app_first_gift'] && $device_code) {
                    (new BaseApiService())->appWelfare(3, $device_code, $user_info['id'], $device, "首次使用APP快捷登录福利", "APP快捷登录得彩金", "恭喜您使用APP在49图库快捷登录成功，49图库已将使用APP快捷登录福利发送至您的福利中心，七日内有效哦～49图库祝您生活愉快！");
                }
                /** @noinspection PhpExpressionResultUnusedInspection */
                $this->fiveActivity($user_info['id']);
                return $this->apiSuccess(ApiMsgData::LOGIN_API_SUCCESS, $token);
            }
            $this->apiError(ApiMsgData::LOGIN_API_ERROR);
        } catch (ModelNotFoundException $exception) {
            $this->apiError(ApiMsgData::MOBILE_NOT_EXIST);
        }
    }

    /**
     * 图形验证码
     * @return JsonResponse
     */
    public function captcha(): JsonResponse
    {
        return $this->apiSuccess('获取成功！', app('captcha')->create('four', true));
    }

    ////  新聊天
    ///
    /*
    * @desc 独立聊天室
    * @param string $user 模型用户
    * @param string $password {这个地方要用户注册的}登录密码
    * @param string $new_password 重置后的新密码
    * @param string $nickname 用户名称
    * @param bool $change true 修改密码 false 登陆
    * @return array|bool
    */

    /**
     * 永久登陆
     * @param array $data
     * @return JsonResponse|void
     * @throws ApiException
     * @throws CustomException
     */
    public function forever(array $data)
    {
        if (!isset($data['secret']) || $data['secret'] != 'R8ZaD2JZvLMe%$_!K92RiffM_VunA') {
            $this->apiError('关键信息不正确，拒绝登陆');
        }
        unset($data['secret']);
        if (auth('user')->attempt($data)) {
            $userInfo = User::getUserInfoByAccountName($data['account_name']);
            if ($userInfo) {
                if ($userInfo['status'] != 1 || $userInfo['is_lock'] != 2) {
                    throw new CustomException(['message' => ApiMsgData::ACCOUNT_DISABLE_API_SUCCESS]);
                }
                $user_info = $userInfo->toArray();
                $user_info['password'] = $data['password'];
                $token = (new TokenService)->setTokenForever($user_info);
                $ip = $this->getIp();
                // 获取用户IP归属地
                $ipCountry = '';
                if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
                    $ipAddress = IpLocation::getLocation($ip);
                    $ipCountry = $ipAddress['country'] ?? '';
                }

                $updateData = [
                    'last_login_at'     => date('Y-m-d H:i:s'),
                    'last_login_ip'     => filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0,
                    'last_login_area'   => $ipCountry,
                    'last_login_device' => self::deviceType(),
                    'is_online'         => 1,
                ];
                User::where(['id' => $user_info['id']])->increment('login_num');
                User::where(['id' => $user_info['id']])->update($updateData);

                return $this->apiSuccess(ApiMsgData::LOGIN_API_SUCCESS, $token);
            }
        }

        $this->apiError(ApiMsgData::LOGIN_API_ERROR);
    }

    public function sendCurlPostRequest($url, $data) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        // 忽略 SSL 证书验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function loginChat($params)
    {
        $user = auth('user')->user();

        if (!$user) {
            return $this->apiError('登录失效，请重新登录');
        }
        if (!$user->chat_user) {
            $user->chat_user = $this->str_rand(7);
            $user->save();
        }
//        $user->chat_user = 'ab12341';

//        $params['new_password']=md5($data['password']);      // 新密码
//        $params['password']=md5($data['password_current']);  // 老密码
        if (!($params['change'])) {
            // 登录
            $params['password'] = $user['chat_pwd'];
            if (!$params['password']) {
                return $this->apiError('登录失效，请重新登录');
            }
        }

        $password = $params['password'];
        $new_password = $params['new_password'] ?? '';
        $change = isset($params['change']) && $params['change'];

        $login = 'http://104.221.137.34:82/member/check_login.html';
        $register = 'http://104.221.137.34:82/member/reg_member.html';
        $edit = 'http://104.221.137.34:82/member/edit_info.html';
        $data = array(
            'password' => $password,
            'username' => $user->chat_user, // chat_user 聊天室账号
        );
//        dd($data);
        $is_login = Http::withoutVerifying()->withHeaders([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36',
            'Accept' => 'application/json',
        ])->post($login, $data)->json();
        $decode = is_array($is_login) ? $is_login : json_decode($is_login, true);
//        dd($decode, $user->chat_user, $user->id, $password, $new_password, $change);

        if (!isset($decode['code']) || $decode['code'] != 1) {
            // 登陆不成功
//            $data['nickname'] = $this->str_rand(7);
            $data['nickname'] = $this->str_rand__chinese(7);
            $data['repassword'] = $data['password'];
            $data['is_internal'] = 1;
            $is_reg = $this->repeatRg($user, $data, $register);
            if ($is_reg) {
                $is_login = Http::post($login, $data)->body();
                $decode = is_array($is_login) ? $is_login : json_decode($is_login, true);
                if (isset($decode['code']) && $decode['code'] == 1) {
                    return $this->apiSuccess(ApiMsgData::LOGIN_CHAT_SERVICE_SUCCESS, $decode['data']);
                }
            } else {
                return false;
            }
        } else {
            #登陆成功
            if (!$change) {
                return $this->apiSuccess(ApiMsgData::LOGIN_CHAT_SERVICE_SUCCESS, $decode['data']);
            }
            #修改账号密码
            if (isset($decode['data']['token'])) {
                $data = array(
                    'avatar'       => '/static/avatar/90.gif',
                    'old_password' => $password,
                    'password'     => $new_password,
                    'repassword'   => $new_password
                );
                $headers = "membertoken:" . $decode['data']['token'];
//                $changeRe = self::curlPost($data, $edit, $headers);
//                $changeRe = Http::post($edit, $data)->header($headers);
                $changeRe = $this->sendPostRequest($edit, $data, ['membertoken:' . $decode['data']['token']]);

                $decode = is_array($changeRe) ? $changeRe : json_decode($changeRe, true);

                if (isset($decode['code']) && $decode['code'] == 1) {
                    return $this->apiSuccess(ApiMsgData::LOGIN_CHAT_SERVICE_SUCCESS, $decode['data']);
                }
                if (!isset($decode['code']) || $decode['code'] != 1) {
                    throw new \Exception($decode['msg'] ?? $changeRe);
                }
            }

        }
    }


    /*
    * @desc 聊天账号注册
    * @param $data 注册数据
    * @param $url 注册路由
    * @return bool
    */

    public function repeatRg(User $user, array $data, string $url): bool
    {
        $is_register = Http::post($url, $data)->body();

        $decode = is_array($is_register) ? $is_register : json_decode($is_register, true);
        if (isset($decode['code']) && $decode['code'] == 1) {
            if (isset($data['username'])) {
                $user->chat_user = $data['username'];
                return $user->save();
            }
            return true;
        } else {
            if (isset($decode['msg']) && $decode['msg'] == '此昵称已存在') {
                $data['nickname'] = $this->str_rand__chinese(7);
                return $this->repeatRg($user, $data, $url);
            }
            if (isset($decode['msg']) && $decode['msg'] == '此账号已存在') {
                $data['username'] = $this->str_rand(7);
                return $this->repeatRg($user, $data, $url);
            }
            throw new \Exception($decode['msg'] ?? json_encode($decode));
        }
    }

    function sendPostRequest($url, $data, $headers)
    {
        // 初始化cURL会话
        $ch = curl_init();

        // 设置cURL选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // 执行cURL会话并获取响应
        $response = curl_exec($ch);

        // 检查是否有cURL错误发生
        if (curl_errno($ch)) {
            echo 'cURL错误: ' . curl_error($ch);
        }

        // 关闭cURL会话
        curl_close($ch);

        return $response;
    }
}
