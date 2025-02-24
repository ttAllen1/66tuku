<?php

namespace Modules\Api\Services\forecast;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Admin\Models\ForecastBet;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\Forecast;
use Modules\Api\Models\PicDetail;
use Modules\Api\Models\PicForecast;
use Modules\Api\Models\User;
use Modules\Api\Models\UserBet;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Services\follow\FollowService;
use Modules\Api\Services\user\UserGrowthScoreService;
use Modules\Api\Services\user\UserPicForecastService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Exceptions\MessageData;
use Modules\Common\Exceptions\StatusData;
use Modules\Common\Models\UserGoldRecord;

class ForecastService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 参与竞猜初始化数据
     * @return JsonResponse
     */
    public function join(): JsonResponse
    {
        $forecasts = Forecast::query()
            ->orderBy('sort')
            ->where('pid', 0)
            ->where('status', 1)
            ->with([
                'subList' => function ($query) {
                    $query->orderBy('sort');
                }
            ])
            ->get();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $forecasts->toArray());
    }

    /**
     * 参与竞猜初始化数据【新】
     * @return JsonResponse
     */
    public function newJoin(): JsonResponse
    {
        $forecasts = ForecastBet::query()
            ->orderBy('sort')
            ->where('pid', 0)
            ->where('status', 1)
            ->get();

        $configs = AuthActivityConfig::query()
            ->whereIn('k', [
                'forecast_bets_money', 'xg_forecast_bet_time', 'am_forecast_bet_time', 'tw_forecast_bet_time',
                'xjp_forecast_bet_time', 'am_48_forecast_bet_time'
            ])
            ->pluck('v', 'k')->toArray();
        $configs['forecast_bets_money'] = json_decode($configs['forecast_bets_money'], true);

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'forecast_bets' => $forecasts->toArray(),
            'configs'       => $configs
        ]);
    }

    /**
     * 图片竞猜发布
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     * @throws Exception
     */
    public function create($params): JsonResponse
    {
        $id = $params['forecast_type_id'];
        $pic_detail_id = $params['pic_detail_id'];
        $params['position'] = $params['position'] ?? 0;
        $content = $params['content'];
        $params['status'] = $this->getCheckStatus(7) == 1 ? 0 : 1;
        try {
            $forecasts = Forecast::query()->where('pid', 0)->findOrFail($id);
            $this->checkForecastData($pic_detail_id, $id, $params['position'], $content, $forecasts);

            $picData = PicDetail::query()->findOrFail($pic_detail_id, ['issue', 'year', 'lotteryType']);
            $params = collect($picData)->merge(collect($params))->toArray();

            if ((new UserPicForecastService())->create($params)) {
                // 加成长值
                (new UserGrowthScoreService())->growthScore($this->_grow['create_post']);
                return $this->apiSuccess(ApiMsgData::FORECAST_API_SUCCESS);
            }
            throw new Exception(['message' => '添加出错']);
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '数据不存在']);
        }
    }

    /**
     * 检查竞猜数据
     * @param $pictureId
     * @param $id
     * @param $position
     * @param $content
     * @param $forecasts
     * @return void
     * @throws CustomException
     */
    private function checkForecastData($pictureId, $id, $position, $content, $forecasts): void
    {
        if ((new UserPicForecastService())->hasUserJoinForecast($pictureId, $id)) {
            throw new CustomException(['message' => '用户已经参与了该竞猜']);
        }
        if ($this->isMustPosition($id) && !$position) {
            throw new CustomException(['message' => '请先选择位置']);
        }
        $count = count($content);
        if ($count < $forecasts->minCount || $count > $forecasts->maxCount) {
            throw new CustomException(['message' => "所选内容应在{$forecasts->minCount}和{$forecasts->maxCount}之间"]);
        }
    }

    /**
     * 是否需要传递位置信息
     * @param $id
     * @return bool
     */
    private function isMustPosition($id): bool
    {
        if (in_array($id, [3, 5, 6])) {
            return true;
        }

        return false;
    }

    /**
     * 竞猜列表
     * @param $params
     * @return JsonResponse
     */
    public function list($params): JsonResponse
    {
        $pic_detail_id = $params['pic_detail_id'];

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, (new UserPicForecastService())->getPicForecastByPicId($pic_detail_id)->toArray());
    }

    /**
     * 竞猜详情
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail($params): JsonResponse
    {
        $user_forecast_id = $params['id'];

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, (new UserPicForecastService())->detail($user_forecast_id)->toArray());
    }

    /**
     * 竞猜点赞
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow($params): JsonResponse
    {
        $picDiagramBuilder = PicForecast::query()->where('id', $params['id']);

        return (new FollowService())->follow($picDiagramBuilder);
    }

    /**
     * 竞猜投注
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function bet($params): JsonResponse
    {
        $userId = auth('user')->id() ?? 0;
//        if ( $userId != 639 && $userId != 75454 ) {
//            throw new CustomException(['message' => '投注维护中，稍后开放']);
//        }

        if ($params['lotteryType'] == 5) {
//            throw new CustomException(['message' => '当前时间禁止投注']);
        }
        try {
            DB::beginTransaction();
            $forecast = ForecastBet::query()->where('status', 1)->select([
                'id', 'name', 'min_bet_money', 'max_bet_money', 'contents', 'status'
            ])->findOrFail($params['forecast_bet_id'])->toArray();
            $contents = json_decode($forecast['contents'], true);
            $contentsArr = [];
            foreach ($contents as $v) {
                $contentsArr[$v['name']] = $v;
            }
            if (!is_array($params['bet_list'])) {
                $params['bet_list'] = json_decode($params['bet_list'], true);
            }
            $params['bet_list'] = array_map(function ($item) {
                return is_array($item) ? $item : json_decode($item, true);
            }, $params['bet_list']);

            if (!$this->checkTimePass($params['lotteryType'])) {
                throw new CustomException(['message' => '当前时间禁止投注']);
            }
            // 判断传来的玩法和对应的赔率跟服务器是否一致
            foreach ($params['bet_list'] as $k => $v) {
                if (!isset($contentsArr[$v['name']]) || $v['odd'] != $contentsArr[$v['name']]['odd']) {
                    throw new CustomException(['message' => '投注参数被修改，请重新投注']);
                }
            }
            if (!$forecast['contents']) {
                throw new CustomException(['message' => '竞猜实体不支持投注']);
            }
            if ($forecast['max_bet_money'] != "0.00" && $forecast['max_bet_money'] < $params['each_bet_money']) {
                throw new CustomException(['message' => '单注金额超出限制']);
            }
            if ($forecast['min_bet_money'] != "0.00" && $forecast['min_bet_money'] > $params['each_bet_money']) {
                throw new CustomException(['message' => '单注金额低于最小值']);
            }
            $nextIssue = $this->getNextIssue($params['lotteryType']);
            if (!is_numeric($nextIssue)) {
                throw new CustomException(['message' => '当前时间禁止投注']);
            }
//            if ($nextIssue != $params['issue']) {
//                throw new CustomException(['message'=>'当前期数不允许投注']);
//            }

            $userId = auth('user')->id();
            $user = User::query()->where('id', $userId)->select(['id', 'account_balance'])->firstOrFail();
            $bets = count($params['bet_list']); // 注数
            $betMoneys = $bets * $params['each_bet_money'];
            if ($betMoneys != $params['bet_money'] || $betMoneys<=0) {
                throw new CustomException(['message' => '参数有误，请重新投注']);
            }
            if ($user['account_balance'] < $betMoneys) {
                throw new CustomException(['message' => '账户余额不足']);
            }
            $data = [];
            $userBetIds = [];
            $userGolds = [];
            $order_num = UserBet::findAvailableNo();
//            $configs = AuthActivityConfig::query()
//                ->whereIn('k', [
//                    'xg_forecast_bet_time', 'am_forecast_bet_time', 'tw_forecast_bet_time', 'xjp_forecast_bet_time',
//                    'am_48_forecast_bet_time'
//                ])
//                ->pluck('v', 'k')->toArray();
            $cancel_date = Redis::get('lottery_real_open_date_' . $params['lotteryType']);
//            if ($params['lotteryType']==1) {
//                $cancel_time = $this->getCancelTime(1, $cancel_date);
////                $redisData = Redis::get('real_open_1');
////                $redisData = explode(',', $redisData);
////                $redisData[12] = str_replace('点', ':', $redisData[12]);
////                $redisData[12] = str_replace('分', '', $redisData[12]);
////                $cancel_time = date('Y-m-d H:i', strtotime($cancel_date.' '.rtrim($redisData[12]))-600);
////                $cancel_time = $cancel_date.' '.$configs['xg_forecast_bet_time'];
//            } else if ($params['lotteryType']==2) {
//                $cancel_time = $this->getCancelTime(2, $cancel_date);
////                $cancel_time = $cancel_date.' '.$configs['am_forecast_bet_time'];
//            } else if ($params['lotteryType']==3) {
//                $cancel_time = $this->getCancelTime(3, $cancel_date);
////                $cancel_time = $cancel_date.' '.$configs['tw_forecast_bet_time'];
//            } else if ($params['lotteryType']==4) {
//                $cancel_time = $this->getCancelTime(4, $cancel_date);
////                $redisData = Redis::get('real_open_4');
////                $redisData = explode(',', $redisData);
////                $redisData[12] = str_replace('点', ':', $redisData[12]);
////                $redisData[12] = str_replace('分', '', $redisData[12]);
////                $cancel_time = date('Y-m-d H:i', strtotime($cancel_date.' '.rtrim($redisData[12]))-600);
////                $cancel_time = $cancel_date.' '.$configs['xjp_forecast_bet_time'];
//            } else if ($params['lotteryType']==5) {
//                $cancel_time = $this->getCancelTime(5, $cancel_date);
////                $cancel_time = $cancel_date.' '.$configs['am_48_forecast_bet_time'];
//            }
            $cancel_time = $this->getCancelTime($params['lotteryType'], $cancel_date);
            foreach ($params['bet_list'] as $k => $item) {
                $data['forecast_bet_name']  = $forecast['name'];
                $data['lotteryType']        = $params['lotteryType'];
                $data['user_id']            = $userId;
                $data['forecast_bet_id']    = $params['forecast_bet_id'];
                $data['year']               = date('Y');
                $data['date']               = date('Y-m-d');
                $data['each_bet_money']     = $params['each_bet_money'];
                $data['bet_num']            = $item['name'];
                $data['issue']              = $nextIssue;
                $data['odd']                = $contentsArr[$item['name']]['odd'];
                $data['position']           = $params['position'] ?? 0;
                $data['order_num']          = $order_num;
                $data['cancel_time']        = $cancel_time ?? date('Y-m-d H:i:s');
                $data['created_at']         = date('Y-m-d H:i:s');
                $userBetIds = UserBet::query()->insertGetId($data);
                // 金币记录
                $userGolds[$k]['user_id'] = $userId;
                $userGolds[$k]['type'] = 13;
                $userGolds[$k]['gold'] = $params['each_bet_money'];
                $userGolds[$k]['symbol'] = '-';
                if ($k==0) {
                    $userGolds[$k]['balance'] = $user['account_balance']-$params['each_bet_money'];
                } else {
                    $userGolds[$k]['balance'] = $userGolds[$k-1]['balance']-$params['each_bet_money'];
                }
                $userGolds[$k]['user_bet_id'] = $userBetIds;
                $userGolds[$k]['created_at'] = date("Y-m-d H:i:s");
            }
            $res = $user->decrement('account_balance', $betMoneys);
            DB::table('user_gold_records')->insert($userGolds);

            if (!$userBetIds || !$res) {
                throw new CustomException(['message' => '请刷新重试']);
            }
            DB::commit();
        } catch (ModelNotFoundException $exception) {
            throw new CustomException(['message' => '竞猜实体不存在']);
        } catch (\Exception $exception) {
            DB::rollBack();
            if ($exception instanceof CustomException) {
                throw new CustomException(['message' => $exception->getMessage()]);
            }
//            dd($exception->getMessage(), $exception->getLine());
            throw new CustomException(['message' => ApiMsgData::BET_ERROR]);
        }

        return $this->apiSuccess();
    }

    private function getCancelTime($lotteryType, $cancel_date)
    {
        $redisData = Redis::get('real_open_'.$lotteryType);
        $redisData = explode(',', $redisData);
        $redisData[12] = str_replace('点', ':', $redisData[12]);
        $redisData[12] = str_replace('分', '', $redisData[12]);

        return date('Y-m-d H:i', strtotime($cancel_date.' '.rtrim($redisData[12]))-600);
    }

    /**
     * 投注列表
     * @param $params
     * @return JsonResponse
     */
    public function bet_index($params): JsonResponse
    {
        $lotteryType = $params['lotteryType'] ?? 0;
        $userId = auth('user')->id();
        $res = UserBet::query()
            ->where('user_id', $userId)
            ->when($lotteryType, function($query) use ($lotteryType) {
                $query->where('lotteryType', $lotteryType);
            })
            ->latest()
//            ->groupBy('order_num')
//            ->select('order_num')
            ->simplePaginate($params['limit'])
            ->toArray();
        if (!$res['data']) {
            return $this->apiSuccess();
        }
        return $this->apiSuccess('', $res['data']);
        $data = $res['data'];
        $order_num = [];
        foreach($data as $v) {
            $order_num[] = $v;
        }

//        $res = UserBet::query()
//            ->whereIn('order_num', $order_num)
//            ->get()
//            ->toArray();
//        $outputData = [];
//
//        foreach ($res as $row) {
//            $orderNum = $row['order_num'];
//            if (!isset($outputData[$orderNum])) {
//                $outputData[$orderNum] = $row;
//                $outputData[$orderNum]['bet_num'] = [$row['bet_num']];
//            } else {
//                $outputData[$orderNum]['bet_num'][] = $row['bet_num'];
//                $outputData[$orderNum]['bet_num'][] = $row['bet_num'];
//            }
//        }
//        $res = array_values($outputData);
//        foreach($res as $k => $v) {
//            if (is_array($v['bet_num'])) {
//                $res[$k]['bet_num'] = implode('/', $v['bet_num']);
//            }
//        }
//        $this->sortArrayByField($res, 'created_at', SORT_DESC);

        return $this->apiSuccess('', $res);
    }

    /**
     * 投注撤单
     * @param $params
     * @return JsonResponse
     * @throws CustomException
     */
    public function cancel($params): JsonResponse
    {
        try{
            DB::beginTransaction();
            $id = $params['bet_id'];
            $cancel_time = DB::table('user_bets')->where('id', $id)->value('cancel_time');
            if (date('Y-m-d H:i:s') >= $cancel_time) {
                throw new CustomException(['message'=>'当前时间段不能取消']);
            }
            $betInfo = UserBet::query()
                ->where('id', $id)
                ->where('win_status', 0)
                ->lockForUpdate()
                ->firstOrFail(['each_bet_money', 'user_id', 'status'])
                ->toArray();
            if ($betInfo['status'] != 0) {
                throw new CustomException(['message'=>'当前投注不支持撤单']);
            }
            UserBet::query()
                ->where('id', $id)->update([
                    'status'        => -2,
                    'win_status'    => -2,
                    'cancel_at'     => date('Y-m-d H:i:s')
                ]);

            $userInfo = User::query()
                ->lockForUpdate()
                ->where('id', $betInfo['user_id'])
                ->select(['id', 'account_balance'])->firstOrFail()->toArray();
            DB::table('users')->where('id', $betInfo['user_id'])->increment('account_balance', $betInfo['each_bet_money']);
            UserGoldRecord::query()->insert([
               'user_id'    => $betInfo['user_id'],
               'type'       => 15,
               'gold'       => $betInfo['each_bet_money'],
               'balance'    => $userInfo['account_balance']+$betInfo['each_bet_money'],
               'symbol'     => '+',
               'created_at' => date('Y-m-d H:i:s')
            ]);
            DB::commit();
        }catch (\Exception $exception) {
            DB::rollBack();
            if ($exception instanceof ModelNotFoundException) {
                throw new CustomException(['message'=>'当前投注不存在']);
            } else if ($exception instanceof CustomException) {
                throw new CustomException(['message'=>$exception->getMessage()]);
            }
            throw new CustomException(['message'=>'撤单失败，请联系客服']);
        }

        return $this->apiSuccess('撤单成功');
    }

    /**
     * 校验当前时间是否能投注
     * @param $lotteryType
     * @return bool
     */
    private function checkTimePass($lotteryType): bool
    {
        $configs = AuthActivityConfig::query()
            ->whereIn('k', [
                'xg_forecast_bet_time', 'am_forecast_bet_time', 'tw_forecast_bet_time', 'xjp_forecast_bet_time',
                'am_48_forecast_bet_time', 'kl8_forecast_bet_time', 'old_am_forecast_bet_time'
            ])
            ->pluck('v', 'k')->toArray();
        $nextOpenDate = Redis::get('lottery_real_open_date_' . $lotteryType);
        if ($nextOpenDate != date("Y-m-d")) {
            return true;
        }

        if ($lotteryType == 1) {
            if (date("H:i:s") <= $configs['xg_forecast_bet_time']) {
                return true;
            }
        }
        if ($lotteryType == 2) {
            if (date("H:i:s") <= $configs['am_forecast_bet_time']) {
                return true;
            }
        }
        if ($lotteryType == 3) {
            if (date("H:i:s") <= $configs['tw_forecast_bet_time']) {
                return true;
            }
        }
        if ($lotteryType == 4) {
            if (date("H:i:s") <= $configs['xjp_forecast_bet_time']) {
                return true;
            }
        }
        if ($lotteryType == 5) {
            if (date("H:i:s") <= $configs['am_48_forecast_bet_time']) {
                return true;
            }
        }
        if ($lotteryType == 6) {
            if (date("H:i:s") <= $configs['kl8_forecast_bet_time']) {
                return true;
            }
        }
        if ($lotteryType == 7) {
            if (date("H:i:s") <= $configs['old_am_forecast_bet_time']) {
                return true;
            }
        }

        return false;
    }

    private function getOdd()
    {
//        Forecast::query()->
    }

}
