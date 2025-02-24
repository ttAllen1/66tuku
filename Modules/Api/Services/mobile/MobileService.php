<?php

namespace Modules\Api\Services\mobile;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\SmsSend;
use Modules\Api\Models\User;
use Modules\Api\Models\UserBlacklistMobile;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\config\ConfigService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class MobileService extends BaseApiService
{
    /**
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     * @throws ApiException
     */
    public function sendMsg($params): JsonResponse
    {

        if (!$params['mobile'] || !$params['scene']) {
            throw new CustomException(['message'=>'手机号和使用场景必填']);
        }

        $configs = (new ConfigService())->getConfigs(['mobile_max_sends', 'register_gift', 'mobile_blacklist', 'wydun']);

        if ($configs['wydun']) {
            if (!isset($params['NECaptchaValidate'])) {
                return response()->json(['message' => '行为验证码必填', 'status' => 40000], 400);
            }
        } else {
            if (!isset($params['captcha']) || !isset($params['key'])) {
                return response()->json(['message' => '图形验证码必填', 'status' => 40000], 400);
            }
        }

        if ($params['scene'] == 'register' || $params['scene'] == 'login') {
            $userId = 0;
            if ($params['scene'] == 'login') {
                // 先检测该手机号是否存在
                if (!User::query()->where('mobile', $params['mobile'])->value('id')) {
                    throw new CustomException(['message'=>'此手机号不存在，请先注册']);
                }
            }
        } else {
            $userId = auth('user')->id() ?: 0;
            if (!$userId) {
                throw new CustomException(['message'=>'请先登录']);
            }
            if ($params['scene'] == 'withdraw') {
                $params['mobile'] = User::query()->where('id', $userId)->value('mobile');
                if (!$params['mobile']) {
                    throw new CustomException(['message'=>'手机号不存在']);
                }
            }
        }
        $mobile = $params['mobile'];
        $scene = $params['scene'];
        $code = rand(100000, 999999);

        // 判断手机号码段是否在被禁用范围中
        if ($configs['mobile_blacklist']) {
            $mobileBlacks = explode('|', $configs['mobile_blacklist']);
            $lengthTemp = [];
            foreach ($mobileBlacks as $mobileItem) {
                if (!in_array(strlen($mobileItem), $lengthTemp)) {
                    $lengthTemp[] = strlen($mobileItem);
                }
            }
            if ($lengthTemp) {
                foreach ($lengthTemp as $lengthItem) {
                    if (in_array(substr($mobile, 0, $lengthItem), $mobileBlacks)) {
                        throw new CustomException(['message'=>'手机号段已在黑名单，请更换手机号，如有疑问请联系客服！']);
                    }
                }
            }
        }

        // 判断手机号是是否存在黑名单中，存在直接返回验证码发送成功
        $mobileExistsBlacklist = UserBlacklistMobile::query()->where('mobile', $mobile)->exists();
        if ($mobileExistsBlacklist) {
            throw new CustomException(['message'=>'手机号被加入黑名单，请更换手机号，如有疑问请联系客服！']);
        }

        // 判断IP是否已在被禁言用户中,是则把手机号加入黑名单
        if (filter_var($this->getIp(), \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            $isForbidSpeakExists = User::query()->where([
                'register_ip' => ip2long($this->getIp()),
                'is_forbid_speak' => 1
            ])->exists();
            if ($isForbidSpeakExists) {
                UserBlacklistMobile::updateOrCreate([
                    'mobile' => $mobile,
                    'ip' => $this->getIp()
                ]);
                throw new CustomException(['message'=>'手机号被加入黑名单，请更换手机号，如有疑问请联系客服！']);
            }
        }

        if ($params['scene']=='bind') {
            $hasId = User::query()->where('mobile', $mobile)->select(['id'])->value('id');
            if ($hasId) {
                if ($hasId==$userId) {
                    throw new CustomException(['message'=>'已绑定手机号']);
                } else {
                    throw new CustomException(['message'=>'此手机号已绑定到其他用户']);
                }
            }
        }
        if ($configs['mobile_max_sends'] !=0 ) {
            $sends = SmsSend::query()->where('mobile', $mobile)->whereDate('created_at', date('Y-m-d'))->count();
            if ($configs['mobile_max_sends'] <= $sends) {
                throw new CustomException(['message'=>'此手机号今日发送已达上限']);
            }
        }

        $isExistTtl = SmsSend::query()
            ->when($params['scene'] != 'register' && $params['scene'] != 'login', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
//            ->when($params['scene'] == 'register' || $params['scene'] == 'login', function($query) use ($mobile) {
//                $query->where('mobile', $mobile);
//            })
            ->where('mobile', $mobile)
            ->where('scene', $scene)
            ->select(['ttl', 'created_at'])
            ->first();
        if ($isExistTtl && (strtotime($isExistTtl['created_at'])+$isExistTtl['ttl']*60) > time()) {
            throw new CustomException(['message'=>$isExistTtl['ttl'].'分钟时间内，只能发送一次']);
        }

        $data['user_id'] = $userId;
        $data['mobile'] = $mobile;
        $data['scene'] = $scene;
        $data['code'] = $code;
        $data['ttl'] = 5;
        if (!$this->sendSms($data) ) {
            return $this->apiError(ApiMsgData::MOBILE_SEND_ERROR);
        }
        $data['created_at'] = date("Y-m-d H:i:s");
        SmsSend::query()->insert($data);
        if ($params['scene']=='bind' && $configs['register_gift'] ) {
            $hasRegisterGift = User::query()->where('id', $userId)->value('register_gift');
            if ($hasRegisterGift==0) {
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
                $welfareData['msg']['title'] = '绑定手机号得彩金';
                $welfareData['msg']['content'] = "恭喜您在49图库绑定手机号成功，49图库已将彩金发送至您的福利中心，七日内有效哦～49图库祝您生活愉快！";
                $welfareData['valid_receive_date'] = [
                    Carbon::now()->toDateString(),Carbon::now()->addDays(6)->toDateString()
                ];
                (new UserWelfareService())->store($welfareData);
                User::query()->where('id', $userId)->update(['register_gift' =>1]);
            }
        }

        return $this->apiSuccess(ApiMsgData::MOBILE_SEND_SUCCESS);
    }

    /**
     * 校验手机号验证码
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function verify($params): JsonResponse
    {
        $isExistTtl = SmsSend::query()
            ->where('user_id', auth('user')->id())
            ->where('mobile', $params['mobile'])
            ->where('scene', $params['scene'])
            ->where('code', $params['sms_code'])
            ->select(['ttl', 'created_at'])
            ->orderByDesc('created_at')
            ->first();
        if (!$isExistTtl || (strtotime($isExistTtl['created_at'])+$isExistTtl['ttl']*60) < time()) {
            throw new CustomException(['message'=>'验证不存在或已失效']);
        }

        if ($params['scene']=='bind') {
            $res = User::query()->where('mobile', $params['mobile'])->select(['id', 'mobile'])->first();
            if (!$res) {
                User::query()->where('id', auth('user')->id())->update(['mobile'=>$params['mobile']]);
                return $this->apiSuccess('绑定成功');
            }
            if ($res['id'] == auth('user')->id()) {
                return $this->apiSuccess('您已绑定过该手机号');
            } else {
                return $this->apiSuccess('该手机号已绑定其他用户');
            }
        }

        return $this->apiSuccess('校验成功');
    }
}
