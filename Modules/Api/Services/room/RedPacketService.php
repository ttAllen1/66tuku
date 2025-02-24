<?php

namespace Modules\Api\Services\room;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\UserBet;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\RedPacket;
use Modules\Common\Models\UserRed;

class RedPacketService extends BaseApiService
{

    /**
     * 红包列表
     * @return JsonResponse
     */
    public function list(): JsonResponse
    {
        $userId = auth('user')->id();
        $roomList = RedPacket::query()
            ->latest()
            ->whereDate('start_date', date('Y-m-d'))
            ->select(['id', 'name', 'last_nums', 'created_at', 'status', 'valid_date', 'type'])
//            ->where('status', '<>', -1)->get()->toArray()
            ->where('status', 1)->get()->toArray();
        if (!$roomList) {
            return $this->apiSuccess();
        }
        foreach($roomList as $k => $v) {
            if ($v['type'] == 1) {
                if (strtotime($v['valid_date'][0])) {
                    $roomList[$k]['created_at'] = $v['valid_date'][0];
                }
                unset($roomList[$k]['valid_date']);
            }
        }

        $red_id = UserRed::query()->where('user_id', $userId)
            ->pluck('red_id')->toArray();
        if (!$red_id) {
            foreach ($roomList as $k => $v) {
                $roomList[$k]['is_receive'] = false;
            }
        } else {
            foreach ($roomList as $k => $v) {
                if ( in_array($v['id'], $red_id)) {
                    $roomList[$k]['is_receive'] = true;
                } else {
                    $roomList[$k]['is_receive'] = false;
                }
            }
        }

        return $this->apiSuccess('', $roomList);
    }

    /**
     * @param array $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function receive(array $params): JsonResponse
    {
        $id = $params['id'];
        $userId = auth('user')->id();
        $fromIndex = false;
        if (!empty($params['from']) && $params['from']=='index') {
            $fromIndex = true;
        }
        try{
            // 检测改用户是否已领取过该红包
            if($this->checkIsReceive($id, $userId)) {
                throw new CustomException(['message' => '您已经领取过了']);
            }
            // 检测改用户今天是否已发言
            if(!$fromIndex && !$this->checkIsSpeak($userId)) {
                throw new CustomException(['message' => '今天暂无发言哦～']);
            }
            // 判断用户是否用APP登录
//            $last_login_device = DB::table('users')->where('id', $userId)->value('last_login_device');
//            if (!in_array($last_login_device, [2, 3])) {
//                throw new CustomException(['message' => '请用APP重新登录后领取红包～']);
//            }

            $type = DB::table('red_packets')->where('id', $id)->value('type');
            $res = RedPacket::query()
                ->lockForUpdate()
                ->where('id', $id)
                ->where('status', 1)
                ->where('type', $type)
                ->when($type==1, function($query) {
                    $query->where('last_nums', '>', 0);
                })
                ->firstOrFail()->toArray();
            if ($type == 2) {
                if ((date('i')>10 && date('i')<29) || date('i')>40 && date('i')<59) {
                    throw new CustomException(['message' => '此时间段不能领取红包']);
                }
                $startMoney = Redis::get('red_package_start_money') ? Redis::get('red_package_start_money') * 100 : 100;
                $endMoney = Redis::get('red_package_end_money') ? Redis::get('red_package_end_money') * 100 :  500;
                $receiveMoney = number_format(rand($startMoney, $endMoney)/100, 2);
            }
            if ($type == 1) {
                if (!is_numeric($res['moneys']) && !isset($res['moneys'][$res['nums']-$res['last_nums']])) {
                    throw new CustomException(['message' => '红包金额不匹配']);
                }
                if (is_numeric($res['moneys'])) {
                    $receiveMoney = $res['moneys'];
                } else {
                    $receiveMoney = $res['moneys'][$res['nums']-$res['last_nums']];
                }
            }
            DB::beginTransaction();

            // 领取
            $data = [
                'user_id'       => $userId,
                'red_id'        => $id,
                'money'         => $receiveMoney,
                'created_at'    => date('Y-m-d H:i:s')
            ];
            DB::table('user_reds')->insert($data);
            if ($type == 1) {
                if ($res['last_nums']>1) {
                    DB::table('red_packets')->where('id', $id)->decrement('last_nums');
                } else {
                    DB::table('red_packets')->where('id', $id)->decrement('last_nums', 1, ['status'=>0]);
//                DB::table('user_chats')->where('id', $messageId)->update(['message->status' => 0]);
                }
            }

            DB::table('users')->where('id', $userId)->increment('account_balance', $receiveMoney);
            DB::table('user_gold_records')->insert([
                'user_id'       => $userId,
                'type'          => 18,
                'gold'          => $receiveMoney,
                'balance'       => DB::table('users')->where('id', $userId)->value('account_balance'),
                'symbol'        => '+',
                'created_at'    => date('Y-m-d H:i:s')
            ]);
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
            if ($exception instanceof ModelNotFoundException) {
                throw new CustomException(['message' => '红包已抢完']);
            } else if ($exception instanceof CustomException) {
                throw new CustomException(['message' => $exception->getMessage()]);
            }
            Log::error('抢红包失败：', ['message' => $exception->getMessage()]);
            throw new CustomException(['message' => '网络异常，请稍后重试']);
        }

        return $this->apiSuccess('领取成功', ['money'=>$receiveMoney]);
    }

    /**
     * 我的红包列表
     * @param $params
     * @return JsonResponse
     */
    public function receives($params): JsonResponse
    {
        $userId = auth('user')->id();
        $res = UserRed::query()
            ->where('user_id', $userId)
            ->with(['red_info' => function ($query) {
                $query->select(['id', 'name']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $redNum = UserRed::query()
            ->where('user_id', $userId)
            ->count();

        $redMoneyCount = UserRed::query()
            ->where('user_id', $userId)
            ->sum('money');

        return $this->apiSuccess('', [
            'list'  => $res->toArray()['data'],
            'total' => $res->total(),
            'redNum' => $redNum,
            'redMoneyCount' => $redMoneyCount,
        ]);
    }

    /**
     * 红包前50排行榜
     * @return JsonResponse
     */
    public function ranks()
    {
        $top50 = UserRed::query()->select('user_id', 'red_id', DB::raw('SUM(money) as total_money'))
            ->groupBy('user_id')
            ->orderByDesc('total_money')
            ->take(50)
            ->with(['user'=>function($query) {
                $query->select(['id', 'account_name']);
            }, 'red_info'=>function($query) {
                $query->select(['id', 'name']);
            }])
            ->get()->toArray();

        return $this->apiSuccess('', $top50);
    }

    /**
     * 首页红包
     * @return JsonResponse
     */
    public function index_red(): JsonResponse
    {
        // 今日是否有红包
        $userId = auth('user')->id();
        try{
            $today = date('Y-m-d');
            $redInfo = RedPacket::query()
//                ->whereDate('start_date', $today)
                ->whereIn('status', [-1, 1])
                ->where('last_nums', '>', 0)
                ->selectRaw("id, valid_date, JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]')) as least_time")
                ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]')) ASC")
                ->first();
            if (!$redInfo) {
                // 没有红包活动
                $config['red']['status'] = 0;
            } else {
                // 获取当天的活动开始时间和结束时间
                $startEndTimes = DB::table('red_packets')
                    ->whereDate('start_date', $today)
                    ->whereIn('status', [-1, 1])
                    ->selectRaw("
                        MIN(JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]'))) as start_time,
                        MAX(JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]'))) as end_time
                    ")
                    ->first();
                $config['red']['start_time'] = $startEndTimes->start_time ?? null;
                $config['red']['end_time'] = $startEndTimes->end_time ?? null;
                if ($userId) {
                    // 检查用户是否已经领取了最近的红包
                    $isReceived = DB::table('user_reds')
                        ->where('user_id', $userId)
                        ->where('red_id', $redInfo->id)
                        ->exists();
                    if ($isReceived) {
                        // 如果已经领取，寻找下一个未领取的红包
                        $nextRedInfo = RedPacket::query()
                            ->whereDate('start_date', $today)
                            ->whereIn('status', [-1, 1])
                            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(valid_date, '$[0]')) > ?", [$redInfo->least_time])
                            ->first();
                        if (!$nextRedInfo) {
                            // 没有下一个未领取的红包
                            $config['red']['status'] = 0;
                        } else {
                            // 设置下一个红包的信息
                            $config['red']['id'] = $nextRedInfo->id;
                            $config['red']['status'] = 1;
                            $config['red']['from'] = 'index';
                            $config['red']['least'] = Carbon::parse($nextRedInfo->valid_date[0])->toDateTimeString();
//                            $config['red'] = [
//                                'id' => $nextRedInfo->id,
//                                'status' => 1,
//                                'from' => 'index',
//                                'least' => Carbon::parse($nextRedInfo->valid_date[0])->toDateTimeString(),
//                            ];
                        }
                    } else {
                        // 如果还没有领取
                        $config['red']['id'] = $redInfo->id;
                        $config['red']['status'] = 1;
                        $config['red']['from'] = 'index';
                        $config['red']['least'] = Carbon::parse($redInfo->valid_date[0])->toDateTimeString();
//                        $config['red'] = [
//                            'id' => $redInfo->id,
//                            'status' => 1,
//                            'from' => 'index',
//                            'least' => Carbon::parse($redInfo->valid_date[0])->toDateTimeString(),
//                        ];
                    }
                } else {
                    // 未登录用户的红包信息
                    $config['red']['id'] = $redInfo->id;
                    $config['red']['status'] = 1;
                    $config['red']['from'] = 'index';
                    $config['red']['least'] = Carbon::parse($redInfo->valid_date[0])->toDateTimeString();
//                    $config['red'] = [
//                        'id' => $redInfo->id,
//                        'status' => 1,
//                        'from' => 'index',
//                        'least' => Carbon::parse($redInfo->valid_date[0])->toDateTimeString(),
//                    ];
                }
            }
        }catch (\Exception $exception) {
            $config['red']['status'] = 0;
        }
        return $this->apiSuccess('', $config);
    }

    /**
     * 检测用户是否已领取红包
     * @param $redId
     * @param $userId
     * @return bool
     */
    private function checkIsReceive($redId, $userId): bool
    {
        return DB::table('user_reds')
            ->where('user_id', $userId)
            ->where('red_id', $redId)
            ->exists();
    }

    /**
     * 检测用户是否已发言
     * @param $userId
     * @return bool
     */
    private function checkIsSpeak($userId): bool
    {
        return DB::table('user_chats')
            ->where('from_user_id', $userId)
            ->whereDate('created_at', date('Y-m-d'))
            ->exists();
    }
}
