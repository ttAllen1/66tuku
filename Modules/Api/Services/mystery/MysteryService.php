<?php

namespace Modules\Api\Services\mystery;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\HistoryNumber;
use Modules\Api\Models\Mystery;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\MessageData;

class MysteryService extends BaseApiService
{
    /**
     * 玄机锦囊最新一期接口
     * @param $param
     * @return JsonResponse
     */
    public function latest($param): JsonResponse
    {
        try{
            $issue = $this->getIssue($param['lotteryType'], $param['year']);
            $param['lotteryType'] = $param['lotteryType'] == 6 ? 5 : $param['lotteryType'];
            $mystery = Mystery::query()
                ->where('year', $param['year'])
                ->where('lotteryType', $param['lotteryType'])
                ->where('issue', $issue)
                ->firstOrFail();
            if (!$mystery) {
                $mystery = Mystery::query()
                    ->where('lotteryType', $param['lotteryType'])
                    ->where('year', date('Y'))
                    ->latest('issue')
                    ->first();
                if (!$mystery) {
                    $mystery = Mystery::query()
                        ->where('lotteryType', $param['lotteryType'])
                        ->where('year', date('Y')-1)
                        ->latest('issue')
                        ->first();
                }
            }

            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $mystery->toArray());
        }catch (\Exception $exception) {
            return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS);
        }
    }

    /**
     * 玄机锦囊历史接口
     * @param $param
     * @return JsonResponse
     */
    public function history($param): JsonResponse
    {
        $issue = $this->getIssue($param['lotteryType'], $param['year']);
        $param['lotteryType'] = $param['lotteryType'] == 6 ? 5 : $param['lotteryType'];
        $mystery = Mystery::query()
            ->where('year', $param['year'])
            ->where('lotteryType', $param['lotteryType'])
            ->where('issue', '<=', $issue)
            ->orderBy('issue', 'desc')
            ->get();

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $mystery->toArray());
    }

    private function getIssue($lotteryType, $year) {
        $issue = Redis::get('lottery_real_open_issue_'.$lotteryType);
        if (!$issue) {
            $issue = HistoryNumber::query()->where('year', $year)
                ->where('lotteryType', $lotteryType)->latest('lotteryTime')->value('issue');
        }

        return $issue;
    }

    private function create($lotteryType)
    {
        $year = date('Y');
        $res = Mystery::query()->select(['content'])->where('lotteryType', '<>', $lotteryType)->orderByRaw('RAND()')->take(1)->get();
        $data = [];

        foreach ($res as $k => $content) {
            $data[$k]['year'] = $year;
            $data[$k]['issue'] = $k+1;
            $data[$k]['lotteryType'] = 5;
            $data[$k]['title'] = $year.'年第'.($k+1).'期六合彩';
            $data[$k]['content'] = json_encode($content['content']);
            $data[$k]['created_at'] = date('Y-m-d H:i:s');
        }
        DB::table('mysteries')
            ->upsert($data, ['year', 'lotteryType', 'issue']);
    }
}
