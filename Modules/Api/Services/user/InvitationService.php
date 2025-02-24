<?php

namespace Modules\Api\Services\user;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Api\Models\Invitation;
use Modules\Api\Models\User;
use Modules\Api\Models\UserDetail;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\ApiMsgData;
use Modules\Common\Exceptions\CustomException;

class InvitationService extends BaseApiService
{

    /**
     * 我的推广
     * @param array $data
     * @return JsonResponse
     * @throws CustomException
     */
    public function getInvitation(array $data): JsonResponse
    {
        $level = $data['level'] ?? 1;
        $whereArr = ['user_id' => request()->userinfo->id];
        switch ($level)
        {
            case 1:
                $whereArr['level'] = 1;
                break;

            case 2:
                $whereArr['level'] = 2;
                break;

            default:
                throw new CustomException(['message'=>'参数值不存在']);
        }

        $invitationList = Invitation::where($whereArr)
            ->with('user:id,nickname,avatar')
            ->paginate(25)
            ->toArray();
        $info['total'] = Invitation::where($whereArr)->count();
        $info['effective'] = Invitation::where($whereArr)->where('status', 1)->count();
        $info['reward'] = Invitation::where($whereArr)->sum('money');

        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page' => $invitationList['last_page'],
            'current_page' => $invitationList['current_page'],
            'total' => $invitationList['total'],
            'info' => $info,
            'list' => $invitationList['data']
        ]);
    }

    /**
     * 当日推广详情
     * @return JsonResponse
     */
    public function getToDayInvitation(): JsonResponse
    {
        $whereArr = [['user_id' , request()->userinfo->id], ['created_at' , '>=', date('Y-m-d')]];
        $invitationList = Invitation::where($whereArr)
            ->with('user:id,nickname,avatar')
            ->paginate(25)
            ->toArray();
        $info['total'] = Invitation::where($whereArr)->count();
        $info['totalAmount'] = Invitation::where($whereArr)->count();
        $info['canGetAmount'] = Invitation::where($whereArr)->where('status', 0)->sum('money');
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, [
            'last_page' => $invitationList['last_page'],
            'current_page' => $invitationList['current_page'],
            'total' => $invitationList['total'],
            'info' => $info,
            'list' => $invitationList['data']
        ]);
    }

    /**
     * 领取推广佣金
     * @return JsonResponse
     * @throws CustomException
     */
    public function getRewards(): JsonResponse
    {
        $whereArr = [
            ['user_id' , request()->userinfo->id],
            ['created_at' , '>=', date('Y-m-d')],
            ['status', 0]
        ];
        $amount = Invitation::where($whereArr)->sum('money');
        if ($amount <= 0)
        {
            throw new CustomException(['message'=>'今日未有可领取金额']);
        }
        DB::transaction(function () use($amount, $whereArr) {
            Invitation::where($whereArr)->update(['status' => 1]);
            UserDetail::create(['amount' => $amount, 'incdec' => 1, 'type' => 1, 'remarks' => '邀请好友返佣']);
            User::where(['id' => request()->userinfo->id])->increment('account_balance', $amount);
        });
        return $this->apiSuccess('领取成功');
    }

    /**
     * 月度统计
     * @return JsonResponse
     */
    public function report(): JsonResponse
    {
        $month = date('n');
        $year = date('Y');
        $data = [];
        for ($i = 1; $i <= $month; $i++)
        {
            $invitation = Invitation::where('user_id', request()->userinfo->id)
                ->where([
                    ['created_at', '>=', $year . '-' . $i . '-01'],
                    ['created_at', '<', $year . '-' . ($i + 1) . '-01']
                ]);
            $data[] = [
                'month' => $i,
                'total' => $invitation->count(),
                'effective' => $invitation->count(),
                'amount' => $invitation->where('status', 1)->count(),
            ];
        }
        return $this->apiSuccess(ApiMsgData::GET_API_SUCCESS, $data);
    }

}
