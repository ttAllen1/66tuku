<?php

namespace Modules\Api\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\AuthConfig;
use Modules\Api\Models\Discuss;
use Modules\Api\Models\FiveBliss;
use Modules\Api\Models\Invitation;
use Modules\Api\Models\Level;
use Modules\Api\Models\User;
use Modules\Api\Models\UserAppFirst;
use Modules\Api\Models\UserComment;
use Modules\Api\Models\UserFiveReceive;
use Modules\Api\Models\UserGrowthScore;
use Modules\Api\Models\UserJoinActivity;
use Modules\Api\Models\UserPlatform;
use Modules\Common\Services\BaseService;
use Tymon\JWTAuth\Facades\JWTAuth;

class BaseApiService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $userId = auth('user')->id();
        if ($userId) {
            Redis::setnx('user_online_time_'.$userId.'_'.date('Y_m_d'), time());
            Redis::expire('user_online_time_'.$userId.'_'.date('Y_m_d'), 12*3600);
        }
    }

    /**
     * 生成邀请码
     * @return string
     */
    protected function randString():string
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .strtoupper(dechex(date('m')))
            .date('d').substr(time(),-5)
            .substr(microtime(),2,5)
            .sprintf('%02d',rand(0,99));
        for (
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < 8;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }

    /**
     * 判断是否https
     * @return bool
     */
    protected function is_ssl() {
        if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS'])))
        {
        } else if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && ('https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] )) {
            return true;
        } else if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_encode($_SERVER['HTTP_CF_VISITOR'], true);
            if (isset($visitor['scheme']) && $visitor['scheme'] == 'https')
            {
                return true;
            }
        } else if (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户信息
     * @return false|object
     */
    protected function userinfo()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $growthScore
     * @return mixed|void
     */
    protected function userLevel($growthScore)
    {
        $level = Level::where('status', 1)->orderByDesc('scores')->get()->toArray();
        $levelNumber = count($level);
        foreach ($level as $key => $item)
        {
            if ($growthScore >= $item['scores'])
            {
                return [
                    'level_name' => $item['level_name'],
                    'level' => $item['id'],
                    'level_grade' => $levelNumber - $key
                ];
            }
        }
        return false;
    }

    /**
     * 查找上二级
     * @param $inviteCode
     * @return array
     */
    private function findSuperior($inviteCode)
    {
        $level[1] = User::getUserId(['invite_code' => $inviteCode]);
        $userid = Invitation::where(['to_userid' => $level[1], 'level' => 1])->value('user_id');
        if ($userid)
        {
            $level[2] = $userid;
        }
        return $level;
    }

    /**
     * 邀请加金币
     * @param $inviteCode
     * @param $userId
     * @return void
     */
    protected function addMoney($inviteCode, $userId)
    {
        $level = $this->findSuperior($inviteCode);
        foreach ($level as $key => $id) {
            $money = 0;
            if ($key == 1)
            {
                $money = AuthConfig::value('level1_money');
            } else if($key == 2) {
                $money = AuthConfig::value('level2_money');
            }
            $invitationData[] = [
                'user_id' => $id,
                'to_userid' => $userId,
                'level' => $key,
                'money' => $money
            ];
        }
        Invitation::insert($invitationData);
        User::where('id', $level[1])->update(['share_gift'=>1]);
    }

    /**
     * 邀请加成长值
     * @param $inviteCode
     * @return void
     */
    protected function addScore($inviteCode)
    {
        $level = $this->findSuperior($inviteCode);
        foreach ($level as $key => $id) {
            $score = 0;
            if ($key == 1)
            {
                // 加集五福进度
                $this->joinActivities(6, $id);
                User::where('id', $id)->update(['share_gift'=>1]);
                $score = AuthConfig::value('level1_score');
            } else if($key == 2) {
                $score = AuthConfig::value('level2_score');
            }
            $countNumber = UserGrowthScore::query()
                ->where('user_id', $id)
                ->where('type', $key)
                ->where('created_at', '>=', date('Y-m-d'))
                ->count();
            if ($countNumber < 5) {
                User::where('id', $id)->increment('growth_score', $score);
                $growthScoreData[] = [
                    'user_id' => $id,
                    'type' => $key,
                    'score' => $score,
                    'date' => date('Y-m-d'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        }
        if (isset($growthScoreData)) {
            UserGrowthScore::insert($growthScoreData);
        }
    }

    protected function appWelfare($type, $device_code, $userId, $device='', $name='', $title='', $content='')
    {
        $ip = $this->getIp();
        $ip = filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) ? ip2long($ip) : 0;
        if ( !$device_code || UserAppFirst::query()->where('device_code', $device_code)->value('id') || User::query()->where('id', $userId)->value('app_first_gift')) {
            return;
        }
        if ($ip != 0) {
            $counts = UserAppFirst::query()->where('ip', $ip)->count('id');
            if ($counts>=2) {
                return;
            }
        }
        $app_first_species = AuthActivityConfig::query()->where('k', 'app_first_species')->value('v');
        if (!is_numeric($app_first_species)) {
            $app_first_species = json_decode($app_first_species, true);
            $app_first_species = rand($app_first_species[0], $app_first_species[1]);
        }
        // 增加注册彩金
        $welfareData = [];
        $welfareData['user_id'] = [$userId];
        $welfareData['name'] = $name;
        $welfareData['is_random'] = 0;
        $welfareData['really_money'] = $app_first_species;
        $welfareData['is_limit_time'] = 1;
        $welfareData['status'] = 0;
        $welfareData['is_send_msg'] = 1;
        $welfareData['msg']['title'] = $title;
        $welfareData['msg']['content'] = $content;
        $welfareData['valid_receive_date'] = [
            Carbon::now()->toDateString(),Carbon::now()->addDays(6)->toDateString()
        ];
        (new UserWelfareService())->store($welfareData);

        UserAppFirst::query()->insert([
            'device'        => $device == 'ios' ? 1 : 2,
            'device_code'   => $device_code,
            'type'          => $type,
            'ip'            => $ip,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        User::query()->where('id', $userId)->update(['app_first_gift'=>1]);
    }


    /**
     * 集五福活动 前三项 注册、绑定、下载
     * @param $userId
     * @return bool
     */
    protected function fiveActivity($userId): bool
    {
        // 五福开关
        if (!$this->fiveBlissOn()) {
            return false;
        }
        if (!$userId) {
            return true;
        }
        try{
            DB::beginTransaction();
            $user = DB::table('users')->where('id', $userId)
                ->select(['id', 'app_first_gift'])
                ->first();
            $aac = AuthActivityConfig::val('five_bliss_start,five_bliss_end');
            $res = UserJoinActivity::query()
                ->where('user_id', $userId)
                ->where([
                    ['created_at', '>=', $aac['five_bliss_start']],
                    ['created_at', '<', $aac['five_bliss_end']],
                ])
                ->lockForUpdate()
                ->get()
                ->toArray();
            $isBind = $this->getIsBindPlat($userId);
            if ( !$res ) {
                $data = [
                    [
                        'user_id'           => $userId,
                        'work_id'           => 1,
                        'five_id'           => 1,
                        'complete_schedule' => 1,
                        'is_finish'         => 1,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s')
                    ],
//                    [
//                        'user_id'           => $userId,
//                        'work_id'           => 1,
//                        'five_id'           => 4,
//                        'complete_schedule' => 1,
//                        'is_finish'         => 0,
//                        'created_at'        => date('Y-m-d H:i:s')
//                    ]
                ];
                if ($isBind) {
                    $data[] = [
                        'user_id'           => $userId,
                        'work_id'           => 1,
                        'five_id'           => 2,
                        'complete_schedule' => 1,
                        'is_finish'         => 1,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s')
                    ];
                }
                if ($user->app_first_gift==1) {
                    $data[] = [
                        'user_id'           => $userId,
                        'work_id'           => 1,
                        'five_id'           => 3,
                        'complete_schedule' => 1,
                        'is_finish'         => 1,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s')
                    ];
                }
                DB::table('user_join_activities')->insert($data);
            } else {
                $today = Carbon::today();
                // 是否已绑定平台号
                $isExistBind = false;
                $isExistAPP = false;
                $isExistRegister = false;
//            $loginIsContinue = 1; // -1断续 0今天已登录 1可续加天数
                foreach($res as $k => $v) {
                    if ( $v['five_id']==1 ) { // 注册
                        $isExistRegister = true;
                    } else if ( $v['five_id']==2 ) {    // 绑定平台
                        $isExistBind = true;
                    } else if ( $v['five_id']==3 ) {    // 下载APP
                        $isExistAPP = true;
                    } else if ( $v['five_id']==4 ) {    // 连续登录
                        // 判断时间的连续性
//                        if ( $v['created_at']<Carbon::yesterday() ){
//                            $loginIsContinue = -1;
//                            if ($v['is_finish'] != 1) {
//                                DB::table('user_join_activities')->where('id', $v['id'])->update(['complete_schedule'=>1]);
//                            }
//                        } else if ( $today->isSameDay(Carbon::parse($v['created_at'])) ){
//                            $loginIsContinue = 0;
//                        } else {
//                            if ( $v['complete_schedule']>=4 ) {
//                                DB::table('user_join_activities')->where('id', $v['id'])->increment('complete_schedule', 1, ['is_finish'=>1]);
//                            } else {
//                                DB::table('user_join_activities')->where('id', $v['id'])->increment('complete_schedule');
//                            }
//                        }
                    }
                }
                $data = [];
                if (!$isExistRegister) {
                    $data[] = [
                        'user_id'           => $userId,
                        'work_id'           => 1,
                        'five_id'           => 1,
                        'complete_schedule' => 1,
                        'is_finish'         => 1,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s')
                    ];
                }
                if (!$isExistBind && $this->getIsBindPlat($userId)) {
                    $data[] = [
                        'user_id'           => $userId,
                        'work_id'           => 1,
                        'five_id'           => 2,
                        'complete_schedule' => 1,
                        'is_finish'         => 1,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s')
                    ];
                }
//                if (!$isExistAPP && $user->app_first_gift==1) {
                if (!$isExistAPP) {
                    $data[] = [
                        'user_id'           => $userId,
                        'work_id'           => 1,
                        'five_id'           => 3,
                        'complete_schedule' => 1,
                        'is_finish'         => 1,
                        'created_at'        => date('Y-m-d H:i:s'),
                        'updated_at'        => date('Y-m-d H:i:s')
                    ];
                }
                if ($data) {
                    DB::table('user_join_activities')->insert($data);
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return true;
        }

        return true;
    }

    /**
     * 用户是否绑定平台
     * @param $userId
     * @return bool
     */
    private function getIsBindPlat($userId): bool
    {
        return (bool)UserPlatform::query()
            ->where([
                'user_id'   => $userId,
                'status'    => 1
            ])
            ->value('id');
    }

    /**
     * 五福红包是否开启
     * @return bool
     */
    protected function fiveBlissOn()
    {
        $activityConfig = AuthActivityConfig::val(['five_bliss_open', 'five_bliss_start', 'five_bliss_end']);
        if (!$activityConfig['five_bliss_open']) {
            return false;
        }
        if (strtotime($activityConfig['five_bliss_start']) > time() || strtotime($activityConfig['five_bliss_end']) <= time()) {
            return false;
        }
        return true;
    }

    /**
     * 集福是否完成 1=完成 2=完成部分 0=未完成
     * @return int
     */
    protected function fiveComplete($getFiveId)
    {
        $aac = AuthActivityConfig::val('five_bliss_start,five_bliss_end,five_bliss_receive_time,five_bliss_show_end');
        $uja = UserJoinActivity::query()
            ->where(['user_id' => auth('user')->id(), 'is_finish' => 1])
            ->where([
                ['created_at', '>=', $aac['five_bliss_start']],
                ['created_at', '<', $aac['five_bliss_end']],
            ])
            ->get();
        if ($uja) {
            if (count($uja->toArray()) == 7 && $getFiveId == -2) {
                return -2;
            } else {
                $gold = 0;
                foreach ($uja as $item) {
                    if (in_array($item->five_id, [1, 2, 3])) {
                        $gold++;
                    } else if($getFiveId == $item->five_id) {
                        $receive = UserFiveReceive::query()
                            ->where(['user_id' => auth('user')->id(), 'five_id' => $item->five_id])
                            ->where([
                                ['created_at', '>=', $aac['five_bliss_start']],
                                ['created_at', '<', $aac['five_bliss_show_end']],
                            ])
                            ->doesntExist();
                        if ($receive) {
                            return $item->five_id;
                        }
                    }
                }
                if ($gold == 3 && $getFiveId == 1) {
                    $receive = UserFiveReceive::query()
                        ->where(['user_id' => auth('user')->id(), 'five_id' => 1])
                        ->where([
                            ['created_at', '>=', $aac['five_bliss_start']],
                            ['created_at', '<', $aac['five_bliss_show_end']],
                        ])
                        ->doesntExist();
                    if ($receive) {
                        return 1;
                    }
                }
                return -1;
            }
        } else {
            return -1;
        }
    }

    /**
     * 获取五福规则
     * @return array
     */
    private function getFiveBliss()
    {
        $fiveBlissData = [];
        $continuousId = [];
        $fiveBliss = FiveBliss::get();
        foreach ($fiveBliss as $item) {
            list($number, $unit) = mb_str_split($item->condition);
            if ($unit == '天') {
                $continuousId[] = $item->id;
            }
            $fiveBlissData[$item->id] = $number;
        }
        return ['continuousId' => $continuousId, 'fiveBlissData' => $fiveBlissData];
    }

    /**
     * 加入积福进度
     * @param $type
     * @param $uid
     * @return bool
     */
    public function joinActivities($type, $uid = false)
    {
        // 五福开关
        if (!$this->fiveBlissOn()) {
            return false;
        }
        $uid = $uid ?: auth('user')->id();
//        $continuous = true;
        $ujaWhere = ['user_id' => $uid, 'work_id' => 1, 'five_id' => $type];
        $aac = AuthActivityConfig::val('five_bliss_start,five_bliss_end');
        $ujaExists = UserJoinActivity::query()
            ->where($ujaWhere)
            ->where([
                ['created_at', '>=', $aac['five_bliss_start']],
                ['created_at', '<', $aac['five_bliss_end']],
            ])
            ->lockForUpdate()
            ->first();
        $fiveBliss = $this->getFiveBliss();
        // 连续性任务处理
        if (in_array($type, $fiveBliss['continuousId'])) {
//            if ($type == 5) {
//                $continuous = UserComment::query();
//            } else {
//                $continuous = Discuss::query();
//            }
            // 检查是否连续
//            $continuous = $continuous->where([
//                ['user_id', $uid],
//                ['created_at', '<', date('Y-m-d')],
//                ['created_at', '>=', date('Y-m-d', strtotime('-1 day'))]
//            ])
//            ->exists();
            // 连续性任务，判断今日是否已经统计
            if ($ujaExists && strtotime($ujaExists['updated_at']) > strtotime(date('Y-m-d'))) {
                return false;
            }
        }
        // 任务完成则无需下面操作
        if ($ujaExists && $ujaExists->is_finish) {
            return false;
        }
        // 判断是否需要检查连续性
//        if ($continuous) {
            if ($ujaExists) {
                $csAdd = $ujaExists->complete_schedule + 1;
                $userJoinActivity = UserJoinActivity::find($ujaExists->id);
                $userJoinActivity->complete_schedule = $csAdd;
                if ($csAdd >= $fiveBliss['fiveBlissData'][$type]) {
                    $userJoinActivity->is_finish = 1;
                }
                $userJoinActivity->save();
            } else {
                $userJoinActivity = new UserJoinActivity();
                $userJoinActivity->user_id = $uid;
                $userJoinActivity->work_id = 1;
                $userJoinActivity->five_id = $type;
                $userJoinActivity->complete_schedule = 1;
                if (1 ==  $fiveBliss['fiveBlissData'][$type]) {
                    $userJoinActivity->is_finish = 1;
                }
                $userJoinActivity->save();
            }
//        } else {
//            if ($ujaExists) {
//                // 连续性类型中断，重置进度
////                UserJoinActivity::where('id', $ujaExists->id)->update(['complete_schedule' => 1]);
//            } else {
//                $userJoinActivity = new UserJoinActivity();
//                $userJoinActivity->user_id = $uid;
//                $userJoinActivity->work_id = 1;
//                $userJoinActivity->five_id = $type;
//                $userJoinActivity->complete_schedule = 1;
//                if (1 ==  $fiveBliss['fiveBlissData'][$type]) {
//                    $userJoinActivity->is_finish = 1;
//                }
//                $userJoinActivity->save();
//            }
//        }
        return true;
    }

    /**
     * 随机数带小数
     * @param $min
     * @param $max
     * @return float|int
     */
    protected function randFloat($min, $max)
    {
        $rand = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return sprintf("%.2f", $rand);
    }

    /**
     * @desc 随机生成21位订单号
     * @param string|null $prefix 可选前缀
     * @return string
     */
    protected static function RandCreateOrderNumber(string $prefix = null)
    {
        $number = date('YmdHms') . rand(100, 999) . substr(str_replace('.', '0', microtime(true)), -4);
        if ($prefix !== null) return strtoupper($prefix) . substr($number, 0, -strlen($prefix));
        return $number;
    }

    /**
     * 获取毫秒时间戳
     * @return float
     */
    protected function microtime_float()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return substr($msectime,0,13);
    }

    /**
     * 获取时间戳 SSS
     * @return float
     */
    protected function get_millisecond(){
        list($usec, $sec) = explode(" ", microtime());
        $msec=round($usec*1000);
        return $msec;
    }
}
