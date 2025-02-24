<?php

namespace Modules\Api\Services\activity;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Jobs\FindRecharge;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\FiveBliss;
use Modules\Api\Models\Invitation;
use Modules\Api\Models\Level;
use Modules\Api\Models\User;
use Modules\Api\Models\UserActivity;
use Modules\Api\Models\UserFivePlatRechargeDate;
use Modules\Api\Models\UserFiveReceive;
use Modules\Api\Models\UserJoinActivity;
use Modules\Api\Models\UserSignin;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\user\UserService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\UserGoldRecord;

class ActivityService extends BaseApiService
{
    /**
     * 转发
     * @return JsonResponse
     */
    public function forward(): JsonResponse
    {
        $this->incForward();
        $type = 'user_activity_forward';
        $this->join($type, $this->_forward_num);

        return $this->apiSuccess(ApiMsgData::FORWARD_API_SUCCESS);
    }

    /**
     * 增加转发次数
     * @return void
     */
    private function incForward()
    {
        User::where('id', auth('user')->id())->increment('forward');
    }

    /**
     * 补填邀请码
     * @param $params
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function filling($params): JsonResponse
    {
        return (new UserService())->fillInviteCode($params['invite_code']);
    }

    /**
     * 活动中心列表
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        // 获取活动配置
        $activityConfig = AuthActivityConfig::query()->get()->toArray();
        $config = [];
        foreach ($activityConfig as $k => $v) {
            $config[$v['k']] = $v['v'];
        }
//        dd($config);
        $userId = auth('user')->id();
        $date = date('Y-m-d');
        $res = [];
        $res['work_doing'] = 0;    // 进行中
        $res['work_receive'] = 0;   // 未领取

        $res['today_works']['follows']['finish'] = 0; // 今日点赞
        $res['today_works']['follows']['gift_num'] = $config['forum_like_species'] ?? 1;
        $res['today_works']['follows']['total'] = $config['forum_like_number'] ?? $this->_follow_num;
        $res['today_works']['follows']['receive'] = false;

        $res['today_works']['comments']['finish'] = 0; // 今日评论
        $res['today_works']['comments']['gift_num'] = $config['forum_comment_species'] ?? 1;
        $res['today_works']['comments']['total'] = $config['forum_comment_number'] ?? $this->_comment_num;
        $res['today_works']['comments']['receive'] = false;

        $res['today_works']['forwards']['finish'] = 0; // 今日转发
        $res['today_works']['forwards']['gift_num'] = $config['forum_forward_species'] ?? 1;
        $res['today_works']['forwards']['total'] = $config['forum_forward_number'] ?? $this->_forward_num;
        $res['today_works']['forwards']['receive'] = false;

        $res['today_works']['online_15']['status'] = false; // 今日在线时长
        $res['today_works']['online_15']['receive'] = false;
        $res['today_works']['online_15']['gift_num'] = $config['online_species'] ?? 1;

        $userActivities = UserActivity::query()
            ->where('user_id', $userId)->where('date', $date)->get()->toArray();
        $time = Redis::get('user_online_time_'.$userId.'_'.date('Y_m_d'));
        // 今日在线时长
        if ($time && (time()-$time) > (($config['online_minute'] ?? 15)*60)) {
            $res['today_works']['online_15']['status'] = true;
            // 判断今日是否领取15分钟奖励
            $flag = false;
            foreach ($userActivities as $k => $v) {
                if ($v['type'] == 'online_15') {
                    $flag = true;
                }
            }
            if (!$flag) {
                UserActivity::query()->create([
                    'user_id'       => $userId,
                    'date'          => $date,
                    'type'          => 'online_15',
                    'is_receive'    => 1
                ]);
                $res['today_works']['online_15']['receive'] = true;
            }
        }
        // 活动任务
        if ($userActivities) {
            foreach ($userActivities as $k => $v) {
                //  转发
                if ($v['type'] == 'user_activity_forward') {        //  转发
//                    if ($v['is_receive'] == 0 ) {
//                        $res['work_doing']++;
//                        $res['today_works']['forwards']['receive'] = false;
//                    } else if ($v['is_receive'] == 1) {
//                        $res['work_receive']++;
//                        $res['today_works']['forwards']['receive'] = true;
//                    } else if ($v['is_receive'] == 2) {
//                        $res['today_works']['forwards']['receive'] = false;
//                    }
//                    $res['today_works']['forwards']['finish'] = $v['num'];
                } else if ($v['type'] == 'user_activity_follow') {  //  点赞
                    if ($v['is_receive'] == 0 ) {
                        $res['work_doing']++;
                        $res['today_works']['follows']['receive'] = false;
                    } else if ($v['is_receive'] == 1) {
                        $res['work_receive']++;
                        $res['today_works']['follows']['receive'] = true;
                    } else if ($v['is_receive'] == 2) {
                        $res['today_works']['follows']['receive'] = false;
                    }
                    $res['today_works']['follows']['finish'] = $v['num'];
                } else if ($v['type'] == 'user_activity_comment') {     // 评论
                    if ($v['is_receive'] == 0 ) {   //  进行中 不能领取
                        $res['work_doing']++;
                        $res['today_works']['comments']['receive'] = false;
                    } else if ($v['is_receive'] == 1) { // 可领取
                        $res['work_receive']++;
                        $res['today_works']['comments']['receive'] = true;
                    } else if ($v['is_receive'] == 2) { // 已领取
                        $res['today_works']['comments']['receive'] = false;
                    }
                    $res['today_works']['comments']['finish'] = $v['num'];
                } else if ($v['type'] == 'online_15') {
                    if ($v['is_receive'] == 0 ) {
                        $res['work_doing']++;
                    } else if ($v['is_receive'] == 1) {
                        $res['work_receive']++;
                    }
                    $res['today_works']['online_15']['receive'] = $v['is_receive'] ==1;
                }
            }
        }

        // 新手任务
        $userGiftInfo = User::query()->select(['account_balance', 'register_gift', 'share_gift', 'filling_gift', 'invite_code'])->find($userId);
        $res['account_balance'] = $userGiftInfo['account_balance'];
        $hasShare = Invitation::query()->where('to_userid', $userId)->first('id'); // 存在：我被人邀请
        if ($hasShare) {
            $res['novice_works']['filling_invite_code']['is_exist'] = true;
            $res['novice_works']['filling_invite_code']['status'] = $userGiftInfo['filling_gift'];
        } else {
            $res['novice_works']['filling_invite_code']['is_exist'] = false;  // 立即补填
            $res['novice_works']['filling_invite_code']['status'] = 0;
            $res['work_receive']++;
        }
//        if ($userGiftInfo['invite_code']) { // todo 有问题
//            $res['novice_works']['filling_invite_code']['is_exist'] = true;
//            $res['novice_works']['filling_invite_code']['status'] = $userGiftInfo['filling_gift'];
//        } else {
//            $res['novice_works']['filling_invite_code']['is_exist'] = false;  // 立即补填
//            $res['novice_works']['filling_invite_code']['status'] = 0;
//        }
        if ($userGiftInfo['register_gift']==0) {
//            $res['work_receive']++;  // 前端没入口 如果关闭注册优惠且不为手机号注册 ==0
        }
//        $res['novice_works']['register_gift'] = $userGiftInfo['register_gift'];
        $res['novice_works']['register_gift'] = 1; // 前端没入口 置为1

//        $hasShare = Invitation::query()->where('user_id', $userId)->first('id');
//        if ($hasShare) {
//            if ($userGiftInfo['share_gift'] == 0 || $userGiftInfo['share_gift'] == 1) {
//                $res['novice_works']['share_gift'] = 1;
//                $res['work_receive']++;
//            } else if ($userGiftInfo['share_gift'] == 2) {
//                $res['novice_works']['share_gift'] = 2;
//            }
//        } else {
//            $res['work_doing']++;
//            $res['novice_works']['share_gift'] = 0;
//        }

        $res['user_info'] = $this->getActivityInformation();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $res);
    }

    /**
     * 领取活动奖励
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function receive($params): JsonResponse
    {
//        $preAmount = DB::table('users')->where('id', auth('user')->id())->value('account_balance');
        try{
            DB::beginTransaction();
            $type = $params['type'];
            $userId = auth('user')->id();
            // 获取金币金额
            switch ($type){
                case 'user_activity_forward':
                    $amountKey = 'forum_forward_species';
                    break;
                case 'user_activity_follow':
                    $amountKey = 'forum_like_species';
                    break;
                case 'user_activity_comment':
                    $amountKey = 'forum_comment_species';
                    break;
                case 'online_15':
                    $amountKey = 'online_species';
                    break;
                case 'filling_gift':
                    $amountKey = 'novice_invitation_species';
                    break;
                case 'register_gift':
                    $amountKey = 'novice_register_species';
                    break;
                case 'share_gift':
                default:
                    $amountKey = 'novice_share_species';
                    break;
            }
            if ($amountKey == 'novice_register_species') {
                $amount = AuthActivityConfig::query()->where('k', $amountKey)->value('v');
                if (!is_numeric($amount)) {
                    $amount = json_decode($amount, true);
                    $amount = rand($amount[0], $amount[1]);
                }
            } else {
                $amount = AuthActivityConfig::query()->where('k', $amountKey)->value('v');
            }

            if (in_array($type, ['user_activity_forward', 'user_activity_follow', 'user_activity_comment', 'online_15'])) {
                $activity = UserActivity::query()
                    ->where('type', $type)
                    ->where('user_id', $userId)
                    ->where('date', date('Y-m-d'))
                    ->select(['id', 'is_receive'])
                    ->lockForUpdate()
                    ->firstOrFail();
                if ( $activity->is_receive != 1) {
                    throw new CustomException(['message'=>'暂时不能领取哦']);
                }
                $activity->update([
                    'is_receive'    => 2,
                    'money'         => $amount
                ]);
                DB::table('users')->where('id', $userId)->increment('account_balance', $amount);
                $this->modifyAccount($userId, $type, $amount);
            } else if (in_array($type, ['filling_gift', 'register_gift'])) {
                if ($type == 'filling_gift') {
                    if (!Invitation::query()->where('to_userid', $userId)->where('level', 1)->lockForUpdate()->value('id')) {
                        throw new CustomException(['message'=>'请先填写邀请码']);
                    }
                    return $this->apiSuccess(ApiMsgData::RECEIVE_API_SUCCESS);
//                    if (UserActivity::query()->where('user_id', $userId)->where('type', $type)->where('is_receive', 2)->lockForUpdate()->value('id')) {
//                        throw new CustomException(['message'=>'邀请码福利已领取']);
//                    }
                }
                if (User::query()->where('id', $userId)->lockForUpdate()->value($type) == 0) {
                    UserActivity::query()->insert([
                        'user_id'       => $userId,
                        'type'          => $type,
                        'num'           => 1,
                        'is_receive'    => 2,
                        'money'         => $amount,
                        'date'          => date('Y-m-d'),
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                    DB::table('users')->where('id', $userId)->increment('account_balance', $amount);
                    $this->modifyAccount($userId, $type, $amount);
                    User::query()->where('id', $userId)->update([$type=>1]);
//                    if ($type == 'filling_gift') {
//                        DB::table('invitations')->where('')
//                        Invitation::query()->insert([
//                            'user_id' => $userId,
//                            'to_userid' => $amount,
//                            'level' => 1,
//                            'money' => $amount
//                        ]);
//                    }
                } else {
                    return $this->apiSuccess('您已经领取过了');
                }
            } else if ($type=='share_gift') {
                if (User::query()->where('id', $userId)->lockForUpdate()->value($type) == 1) {
                    User::query()->where('id', $userId)->update([$type=>2]);
                    UserActivity::query()->insert([
                        'user_id'       => $userId,
                        'type'          => $type,
                        'num'           => 1,
                        'is_receive'    => 2,
                        'money'         => $amount,
                        'date'          => date('Y-m-d'),
                        'created_at'    => date('Y-m-d H:i:s'),
                    ]);
                    DB::table('users')->where('id', $userId)->increment('account_balance', $amount);
                    $this->modifyAccount($userId, $type, $amount);
                }
            }
            DB::commit();
        }catch (CustomException $exception) {
//            DB::rollBack();
            throw new CustomException(['message'=>$exception->getMessage()]);
        }catch (\Exception $exception) {
            DB::rollBack();
            Log::error('活动领取失败', ['message'=>$exception->getMessage()]);
            throw new CustomException(['message'=>'领取失败']);
        }

        return $this->apiSuccess(ApiMsgData::RECEIVE_API_SUCCESS);
    }

    /**
     * 签到信息
     * @return array
     */
    public function getActivityInformation(): array
    {
        $userInfo = [
            'level_name' => $this->userLevel($this->userinfo()->growth_score)['level_name'],
            'growth_score' => $this->userinfo()->growth_score,
        ];
        $prevSignIn = UserSignin::where([['user_id', request()->userinfo->id], ['created_at', '>', date('Y-m-d', strtotime('-1 day'))]])
            ->orderByDesc('created_at')
            ->first();
        if ($prevSignIn)
        {
            $userInfo['signCount'] = $prevSignIn->number;
        } else {
            $userInfo['signCount'] = 0;
        }
        $signIn = UserSignin::where([['user_id', request()->userinfo->id], ['created_at', '>', date('Y-m-d')]])->first();
        if ($signIn)
        {
            $userInfo['isSign'] = 1;
        } else {
            $userInfo['isSign'] = 0;
        }
        $nextLevel = Level::where([['status', 1], ['scores', '>', $userInfo['growth_score']]])->orderBy('scores')->first();
        if ($nextLevel)
        {
            $userInfo['nextLevelName'] = $nextLevel->level_name;
            $userInfo['nextScores'] = $nextLevel->scores;
        } else {
            $userInfo['nextLevelName'] = '';
            $userInfo['nextScores'] = '';
        }

        $userInfo['signInDaySpecies'] = json_decode(AuthActivityConfig::where('k', 'signin_day_species')->value('v'), true);

        return $userInfo;
    }

    /**
     * 参与活动
     * @param $type "点赞 评论 转发 在线时长"
     * @param int $maxCount "满足参与数"
     * @return true
     */
    public function join(string $type, int $maxCount=10): bool
    {
        $userId = auth('user')->id();
        $date = date('Y-m-d');
        $res = UserActivity::query()
            ->firstOrCreate(['user_id'=>$userId, 'date'=>$date, 'type'=>$type], [
                'user_id'=>$userId, 'date'=>$date, 'type'=>$type
            ]);
        if ($res->is_receive != 0) {
            return true;
        }
        if ($res->num<$maxCount) {
            $res->increment('num');
        }
        if ($res->num == $maxCount) {
            $res->update(['is_receive'=>1]);
        }

        return true;
    }

    public function modifyAccount($userId, $type, $amount, $user_welfare_id=0)
    {
        // 类型：1帖子点赞；2评论帖子；3转发帖子；4在线时长；5补填邀请码；6注册金币；7分享好友；8签到；9充值【平台->图库 plat_recharge】；10提现【图库->平台 plat_withdraw】；11福利【user_welfare】;12 充值【平台->图库 plat_recharge】【撤回】

//        类型：user_activity_forward（转发）user_activity_follow（点赞）user_activity_comment（评论）online_15（在线15分钟）filling_gift（补填邀请码）register_gift（新手注册红包）share_gift（分享红包）
//        User::query()->where('id', $userId)->increment('account_balance');
        switch ($type){
            case 'user_activity_forward':
                $type = 3;
                break;
            case 'user_activity_follow':
                $type = 1;
                break;
            case 'user_activity_comment':
                $type = 2;
                break;
            case 'online_15':
                $type = 4;
                break;
            case 'filling_gift':
                $type = 5;
                break;
            case 'register_gift':
                $type = 6;
                break;
            case 'share_gift':
                $type = 7;
                break;
            case 'user_sign':
                $type = 8;
                break;
            case 'plat_recharge':
                $type = 9;
                break;
            case 'plat_withdraw':
                $type = 10;
                break;
            case 'user_welfare':
                $type = 11;
                break;
            case 'revoke_plat_recharge':
                $type = 12;
                break;
            case 'plat_withdraw_cancel':
                $type = 17;
                break;
            case 'five_success':
                $type = 19;
                break;
            case 22:
                $type = 22;
                break;
            case 23:
                $type = 23;
                break;
            case 24:
                $type = 24;
                break;
            case 25:
                $type = 25;
                break;
            case 26:
                $type = 26;
                break;
            case 27:
                $type = 27;
                break;
            case 28:
                $type = 28;
                break;
            case 29:
                $type = 29;
                break;
        }
        $account_balance = User::query()->where('id', $userId)->value('account_balance');
        UserGoldRecord::query()->create([
            'user_id'           => $userId,
            'type'              => $type,
            'gold'              => $amount,
            'symbol'            => in_array($type, [1, 2, 3, 4, 5, 6, 7, 8, 9, 11, 12, 17, 19, 23, 25, 27, 29]) ? '+' : '-',
            'balance'           => $account_balance,
            'user_welfare_id'   => $user_welfare_id,
            'created_at'        => date('Y-m-d H:i:s')
        ]);

    }

    /**
     * 集五福进度
     * @return JsonResponse
     */
    public function five_schedule()
    {
        $uid = auth('user')->id();
        $fiveBliss = FiveBliss::get();
        $aac = AuthActivityConfig::val('five_bliss_start,five_bliss_end,five_bliss_receive_time,five_bliss_show_end,five_bliss_count');
        $fiveBlissCount = UserJoinActivity::query()
            ->where('is_finish', 1)
            ->where([
                ['created_at', '>=', $aac['five_bliss_start']],
                ['created_at', '<', $aac['five_bliss_end']],
            ])
            ->groupBy('user_id')
            ->havingRaw('count(user_id) = 7')
            ->count();
        $fiveBlissCount += $aac['five_bliss_count'];
        if ($uid) {
            // 五福统计
            $this->fiveActivity($uid);

            $userJoinActivity = UserJoinActivity::query()
                ->where(['user_id' => $uid])
                ->where([
                    ['created_at', '>=', $aac['five_bliss_start']],
                    ['created_at', '<', $aac['five_bliss_end']],
                ])
                ->get();
            if ($userJoinActivity) {
                $fiveBlissNumber = [];
                foreach ($fiveBliss as $fiveBlissItem) {
                    $fiveBlissNumber[$fiveBlissItem->id] = mb_str_split($fiveBlissItem->condition)[0];
                }
                $fiveBlissGold = 0;
                foreach ($userJoinActivity as $item) {
                    if (in_array($item->five_id, [1, 2, 3])) {
                        $fiveBlissGold++;
                    }
                    foreach ($fiveBliss as &$fiveBlissItems) {
                        if ($item->five_id == $fiveBlissItems->id) {
                            $fiveBlissItems->schedule = intval($item->complete_schedule / $fiveBlissNumber[$item->five_id] * 100);
                            $fiveBlissItems->completed = $item->complete_schedule;
                            if ($fiveBlissItems->schedule == 100) {
                                $isReceive = UserFiveReceive::query()
                                    ->where(['user_id' => auth('user')->id(), 'five_id' => $item->five_id])
                                    ->where([
                                        ['created_at', '>=', $aac['five_bliss_start']],
                                        ['created_at', '<', $aac['five_bliss_show_end']],
                                    ])
                                    ->first();
                                if ($isReceive) {
                                    $fiveBlissItems->isReceive = 1;
                                    $fiveBlissItems->money = $isReceive->money;
                                } else {
                                    $fiveBlissItems->isReceive = 0;
                                    $fiveBlissItems->money = 0;
                                }
                            } else {
                                $fiveBlissItems->isReceive = 0;
                                $fiveBlissItems->money = 0;
                            }
                            break;
                        }
                    }
                }
                // 未加入的直接赋值为0
                foreach ($fiveBliss as &$fiveBlissItem) {
                    if (!isset($fiveBlissItem->schedule)) {
                        $fiveBlissItem->schedule = 0;
                        $fiveBlissItem->completed = 0;
                        $fiveBlissItem->isReceive = 0;
                        $fiveBlissItem->money = 0;
                    }
                }
                // 金单独判断进度
                $gold = intval($fiveBlissGold / 3 * 100);
                $isReceive = 0;
                $goldMoney = 0;
                if ($gold == 100) {
                    $isGoldReceive = UserFiveReceive::query()
                        ->where(['user_id' => auth('user')->id(), 'five_id' => 1])
                        ->where([
                            ['created_at', '>=', $aac['five_bliss_start']],
                            ['created_at', '<', $aac['five_bliss_show_end']],
                        ])
                        ->first();
                    if ($isGoldReceive) {
                        $isReceive = 1;
                        $goldMoney = $isGoldReceive->money;
                    } else {
                        $isReceive = 0;
                        $goldMoney = 0;
                    }
                }
                if ($this->fiveBlissOn()) {
                    // 加入列队查询是否平台充值
                    FindRecharge::dispatch($uid)->onQueue('queue_find_recharge' . rand(1, 10));
                }
                $receive = UserFiveReceive::query()
                    ->where(['user_id' => $uid, 'five_id' => -2])
                    ->where([
                        ['created_at', '>=', $aac['five_bliss_receive_time']],
                        ['created_at', '<', $aac['five_bliss_show_end']],
                    ])
                    ->first();
                $received = 0;
                $maxMoney = 0;
                if ($receive) {
                    $received  = 1;
                    $maxMoney = $receive->money;
                }
                return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['gold' => ['schedule' => $gold, 'receive' => $isReceive, 'money' => $goldMoney], 'list' => $fiveBliss, 'ready' => $aac['five_bliss_receive_time'], 'received' => $received, 'maxMoney' => $maxMoney, 'fiveBlissCount' => $fiveBlissCount]);
            }
        }
        // 未登录直接进度赋值为0
        foreach ($fiveBliss as &$fiveBlissItem) {
            $fiveBlissItem->schedule = 0;
            $fiveBlissItem->completed = 0;
            $fiveBlissItem->isReceive = 0;
            $fiveBlissItem->money = 0;
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, ['gold' => ['schedule' => 0, 'receive' => 0, 'money' => 0], 'list' => $fiveBliss, 'ready' => $aac['five_bliss_receive_time'], 'received' => 0, 'fiveBlissCount' => $fiveBlissCount]);
    }

    /**
     * 五福红包领取
     * @return JsonResponse
     * @throws CustomException
     */
    public function five_receive($getFiveId)
    {
        try {
            if (!$getFiveId || $getFiveId == 2 || $getFiveId == 3) {
                throw new \Exception('参数错误');
            }
            if (intval($getFiveId) != -2) {
                $fiveBlissExist = FiveBliss::query()->where('id', intval($getFiveId))->doesntExist();
                if ($fiveBlissExist) {
                    throw new \Exception('参数错误');
                }
            }
            DB::beginTransaction();
            $aac = AuthActivityConfig::val('five_bliss_open,five_bliss_receive_time,five_bliss_show_end,five_bliss_gold_min,five_bliss_gold_max,five_bliss_start,five_bliss_end');
            if (!$aac['five_bliss_open']) {
                throw new \Exception('活动未开启');
            }
            if ((strtotime($aac['five_bliss_receive_time']) <= time() && time() < strtotime($aac['five_bliss_show_end']) && $getFiveId == -2) || $getFiveId != -2) {
                $receive = UserFiveReceive::query()
                    ->where(['user_id' => auth('user')->id(), 'five_id' => $getFiveId])
                    ->where([
                        ['created_at', '>=', $aac['five_bliss_start']],
                        ['created_at', '<', $aac['five_bliss_show_end']],
                    ])
                    ->lockForUpdate()
                    ->first();
                if ($receive) {
                    throw new \Exception('已经领取');
                }
                $fiveId = $this->fiveComplete($getFiveId);
                switch ($fiveId){
                    case -2:
                        $rechargeNumber = UserFivePlatRechargeDate::query()
                            ->where('user_id',  auth('user')->id())
                            ->where([
                                ['created_at', '>=', $aac['five_bliss_start']],
                                ['created_at', '<', $aac['five_bliss_end']],
                            ])
                            ->sum('money');
                        $randStart = 0;
                        $randEnd = 0;
                        $max = unserialize($aac['five_bliss_gold_max']);
                        foreach ($max as $maxItem) {
                            if ($maxItem[1] == '~') {
                                if ($rechargeNumber >= $maxItem[0]) {
                                    $randStart = $maxItem[2];
                                    $randEnd = $maxItem[3];
                                    break;
                                }
                            } else {
                                if ($rechargeNumber >= $maxItem[0] && $rechargeNumber < $maxItem[1]) {
                                    $randStart = $maxItem[2];
                                    $randEnd = $maxItem[3];
                                    break;
                                }
                            }
                        }
                        $money = $this->randFloat($randStart, $randEnd);
                        $money = $money + $rechargeNumber * 0.03;
                        break;

                    case -1:
                        throw new \Exception('未有完成任务');

                    default:
                        $min = unserialize($aac['five_bliss_gold_min']);
                        $money = $this->randFloat($min[0], $min[1]);
                        break;
                }
                UserFiveReceive::insert([
                    'user_id' => auth('user')->id(),
                    'five_id' => $getFiveId,
                    'money' => $money
                ]);
                $this->modifyAccount(auth('user')->id(),'five_success', $money);
                User::where('id', auth('user')->id())->increment('account_balance', $money);
                DB::commit();
                return $this->apiSuccess(ApiMsgData::RECEIVE_API_SUCCESS, ['money' => $money]);
            } else {
                throw new \Exception('活动结束');
            }
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new CustomException(['message'=>$exception->getMessage()]);
        }
    }

}
