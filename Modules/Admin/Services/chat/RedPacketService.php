<?php

namespace Modules\Admin\Services\chat;

use Carbon\Carbon;
use GatewayClient\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Api\Events\ChatEvent;
use Modules\Api\Models\User;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\RedPacket;
use Modules\Api\Services\room\RoomService;

class RedPacketService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    /**
     * 聊天室红包列表
     * @param $params
     * @return JsonResponse
     */
    public function list($params): JsonResponse
    {
        $redList = RedPacket::query()->latest()->paginate($params['limit'])->toArray();

        $rData = [];
        $rData['red_package_type'] = (int)Redis::get('red_package_type') ?: 1;
        $rData['auto_switch'] = (int)Redis::get('red_package_auto_switch') ?: 0;
        $rData['startHour'] = Redis::get('red_package_start') ?: 18;
        $rData['endHour'] = Redis::get('red_package_end') ?: 21;
        $rData['startMoney'] = Redis::get('red_package_start_money') ?: 1;
        $rData['endMoney'] = Redis::get('red_package_end_money') ?: 5;
        $rData['names'] = Redis::get('red_package_names');
        return $this->apiSuccess('', [
            'list'  => $redList['data'],
            'total' => $redList['total'],
            'rData' => $rData,
        ]);
    }

    /**
     * 红包随机金额
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function round_num($params): JsonResponse
    {
        if (!$params['total'] || !$params['nums']) {
            throw new CustomException(['message'=>'红包总金额或总数量不能为空']);
        }
        if (!is_numeric($params['total']) || !is_numeric($params['nums'])) {
            throw new CustomException(['message'=>'红包总金额或总数量应为数字']);
        }
        // 如何红包总金额和数量一致
        if ($params['total'] == $params['nums']) {
            return $this->apiSuccess('', [
                'data' => json_encode(array_fill(0, $params['nums'], '1.00'))
            ]);
        }

        $total = intval($params['total'] * 100); // 将总金额转换为整数，以避免浮点数精度问题
        $nums = intval($params['nums']); // 将总数量转换为整数

        // 计算最小和最大红包金额
        $minAmount = 100; // 每个红包的最小金额，假设为1元
        $maxAmount = intval($total / $nums * 2); // 每个红包的最大金额为总金额的平均值的两倍

        // 生成随机红包金额数组
        $data = [];
        for ($i = 0; $i < $nums - 1; $i++) {
            if ($i == 0) {
                // 第一个红包的金额
                $max = min($maxAmount, $total - $nums * $minAmount); // 确保红包金额不超过最大限制
            } else {
                // 其他红包的金额
                $max = min($maxAmount, ($total - array_sum($data)) - ($nums - $i - 1) * $minAmount); // 确保红包金额不超过最大限制
            }
            $data[$i] = mt_rand($minAmount, $max);
        }
        $data[$nums - 1] = $total - array_sum($data); // 最后一个红包的金额为总金额减去前面红包金额的和
        $aroundArr = array_map(function ($item) {
            return number_format($item / 100, 2);
        }, $data);
        if (number_format(array_sum($aroundArr),2) != number_format($params['total'], 2)) {
            return $this->round_num(['total'=>$params['total'], 'nums'=>$params['nums']]);
        }
        shuffle($aroundArr);

        return $this->apiSuccess('', [
            'data' => json_encode($aroundArr)
        ]);
    }

    /**
     * 创建红包
     * @param $params
     * @return JsonResponse
     * @throws ApiException
     */
    public function store($params): JsonResponse
    {
        try{
            if ($params['type'] == 2) {
                Redis::set('red_package_type', 2);
                Redis::set('red_package_auto_switch', $params['auto_switch']);
                Redis::set('red_package_start', $params['startHour']);
                Redis::set('red_package_end', $params['endHour']);
                Redis::set('red_package_start_money', $params['startMoney']);
                Redis::set('red_package_end_money', $params['endMoney']);
                Redis::set('red_package_names', $params['names']);

                return $this->apiSuccess('自动红包开启成功，将在指定时间发送到聊天室');
            }
            DB::beginTransaction();

            unset($params['id']);
            $params['last_nums'] = $params['nums'];
            if (!$params['moneys'] || $params['moneys'] == '[]') {
                throw new CustomException(['message'=>'红包金额不能为空']);
            }
            if (!$params['total'] || !$params['nums']) {
                throw new CustomException(['message'=>'红包总金额或总数量不能为空']);
            }
            $params['start_date'] = date('Y-m-d');
            if ($params['is_immediately'] == 0) {
                $params['status'] = -1;
                if (!isset($params['valid_date']) || !is_array($params['valid_date'])) {
                    throw new CustomException(['message'=>'红包发放时间必填']);
                }
                $params['start_date'] = Carbon::parse($params['valid_date'][0])->format('Y-m-d');
                $params['valid_date'] = json_encode([
                    Carbon::parse($params['valid_date'][0])->format('Y-m-d H:i:00'),
                    Carbon::parse($params['valid_date'][1])->format('Y-m-d H:i:00'),
                ]);
            } else { // 立即发放
                $params['status'] = 1;
                $params['start_date'] = date('Y-m-d');
                if ($params['end_date']) {
                    $params['valid_date'] = json_encode([0, Carbon::parse($params['end_date'])->format('Y-m-d H:i:00')]);
                } else {
                    $params['valid_date'] = json_encode([0, 0]);
                }
            }
            $client_id = $params['client_id'];
            unset($params['client_id']);
            unset($params['end_date']);
            $params['created_at'] = date('Y-m-d H:i:s');
            $redId = RedPacket::query()->insertGetId($params);
            if (!$redId) {
                throw new CustomException(['message'=>'红包新增失败']);
            }
            $room_id = 5;
            $from = 54377;
            if ($params['is_immediately'] == 1) {
                // 发送到聊天室
                $sendData = [
                    'room_id'   => $room_id,
                    'message'   => ['status'=>1, 'redId'=>$redId, 'is_receive'=>false],
                    'style'     => 'red_envelope',
                    'type'      => 'all',
                    'to'        => [],
                ];
                $sendData['uuid'] = Str::uuid();
                $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->findOrFail($from);
                $fromSession = [
                    'user_id'           => $from,
                    'user_name'         => $userData->nickname,
                    'avatar'            => $userData->avatar,
                    'room_id'           => $room_id,
                ];
                $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData);

                $room_check = Redis::get("chat_room_check_".$fromSession['room_id']);
                Gateway::sendToGroup($fromSession['room_id'], json_encode(['type'=>$sendMsg['type'], 'data'=>$sendMsg['data']]));

                // 保存数据
                $sendMsg['from_user_id'] = $from;

                $sendMsg['status'] = $room_check == 0 ? 1 : 0;
                event(new ChatEvent($sendMsg));
                DB::commit();
                Redis::set('red_package_type', 1);
                return $this->apiSuccess('红包已投放到聊天室');
            } else {
                DB::commit();
                Redis::set('red_package_type', 1);
                return $this->apiSuccess('红包投放成功，将在指定时间发送到聊天室');
            }
        }catch (\Exception $exception) {
            DB::rollBack();
//            dd($exception->getFile(), $exception->getLine());
            return $this->apiError($exception->getMessage());
        }

    }

    /**
     * 修改红包
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function update($params): JsonResponse
    {
        try{
            DB::beginTransaction();
            $params['last_nums'] = $params['nums'];
            if (!$params['moneys'] || $params['moneys'] == '[]') {
                throw new CustomException(['message'=>'红包金额不能为空']);
            }
            $status = DB::table('red_packets')->lockForUpdate()->where('id', $params['id'])->value('status');
            if ($status != -1) {
                throw new CustomException(['message'=>'该红包此状态禁止修改']);
            }
            $params['start_date'] = date('Y-m-d');
            if ($params['is_immediately'] == 0) {
                $params['status'] = -1;
                if (!isset($params['valid_date']) || !is_array($params['valid_date'])) {
                    throw new CustomException(['message'=>'红包发放时间必填']);
                }
                $params['start_date'] = Carbon::parse($params['valid_date'][0])->format('Y-m-d');
                $params['valid_date'] = json_encode([
                    Carbon::parse($params['valid_date'][0])->format('Y-m-d H:i:00'),
                    Carbon::parse($params['valid_date'][1])->format('Y-m-d H:i:00'),
                ]);
            } else { // 立即发放
                $params['status'] = 1;
                $params['start_date'] = date('Y-m-d');
                if (!empty($params['end_date'])) {
                    $params['valid_date'] = json_encode([0, Carbon::parse($params['end_date'])->format('Y-m-d H:i:00')]);
                } else {
                    $params['valid_date'] = json_encode([0, 0]);
                }
            }
            $client_id = $params['client_id'];
            unset($params['client_id']);
            unset($params['end_date']);
//            $params['created_at'] = date('Y-m-d H:i:s');
            $redId = RedPacket::query()->where('id', $params['id'])->update($params);
            if (!$redId) {
                throw new CustomException(['message'=>'红包更新失败']);
            }
            $room_id = 5;
            $from = 54377;
            if ($params['is_immediately'] == 1) {
                // 发送到聊天室
                $sendData = [
                    'room_id'   => $room_id,
                    'message'   => ['status'=>1, 'redId'=>$params['id'], 'is_receive'=>false],
                    'style'     => 'red_envelope',
                    'type'      => 'all',
                    'to'        => [],
                ];

//                (new RoomService())->chat($sendData, $from, [$client_id]);
                $sendData['uuid'] = Str::uuid();
                $userData = User::query()->select(['id', 'nickname', 'avatar', 'is_forbid_speak'])->findOrFail($from);
                $fromSession = [
                    'user_id'           => $from,
                    'user_name'         => $userData->nickname,
                    'avatar'            => $userData->avatar,
                    'room_id'           => $room_id,
                ];
                $sendMsg = (new RoomService())->sendMsg('chat_ok', $fromSession, $sendData);

                $room_check = Redis::get("chat_room_check_".$fromSession['room_id']);
                Gateway::sendToGroup($fromSession['room_id'], json_encode(['type'=>$sendMsg['type'], 'data'=>$sendMsg['data']]));

                // 保存数据
                $sendMsg['from_user_id'] = $from;

                $sendMsg['status'] = $room_check == 0 ? 1 : 0;
                event(new ChatEvent($sendMsg));
                DB::commit();
                return $this->apiSuccess('红包已投放到聊天室');
            } else {
                DB::commit();
                return $this->apiSuccess('红包修改成功');
            }
        }catch (\Exception $exception) {
            DB::rollBack();
//            dd($exception->getMessage(), $exception->getLine(), $exception->getFile());
            if ($exception instanceof CustomException) {
                throw new CustomException(['message'=>$exception->getMessage()]);
            }
            throw new CustomException(['message'=>'红包更新失败:'.$exception->getMessage()]);
        }
    }
}
