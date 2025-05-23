<?php

namespace Modules\Admin\Services\user;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Services\BaseApiService;
use Modules\Common\Models\UserGoldRecord;

class UserGoldService extends BaseApiService
{
    /**
     * 列表
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $account_name = $data['account_name'] ?? '';
        $date_range = $data['date_range'] ?? [];
        $userId = 0;
        if ($account_name) {
            $userId = DB::table('users')->where('account_name', $account_name)->value('id');
        }
        $list = UserGoldRecord::query()
            ->when($userId, function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($date_range, function($query) use ($date_range) {
                $query->where('created_at', '>=', $this->getDateFormat($date_range[0]))
                    ->where('created_at', '<=', $this->getDateFormat($date_range[1]));
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'account_name', 'account_balance']);
            }, 'bet'=>function($query) {
                $query->select(['id', 'status', 'win_status']);
            }, 'welfare'=>function($query) {
                $query->select(['id', 'name']);
            }, 'reward'=>function($query) {
                $query->select(['id', 'type']);
            }, 'posts'=>function($query) {
                $query->select(['id', 'type']);
            }, 'market'=>function($query) {
                $query->select(['id', 'type']);
            }])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($data['limit'])->toArray();
//        dd($list['data']);
        if ($list['data']) {
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['gold'] = str_replace('.00', '', $v['gold']);
                $type = '未知';
//                user_activity_forward（转发）user_activity_follow（点赞）user_activity_comment（评论）online_15（在线15分钟）filling_gift（补填邀请码）register_gift（新手注册红包）share_gift（分享红包）
                switch ($v['type']){
                    case 3:
                        $type = '转发【user_activity_forward】';
                        break;
                    case 1:
                        $type = '点赞【user_activity_follow】';
                        break;
                    case 2:
                        $type = '评论【user_activity_comment】';
                        break;
                    case 4:
                        $type = '在线15分钟【online_15】';
                        break;
                    case 5:
                        $type = '补填邀请码【filling_gift】';
                        break;
                    case 6:
                        $type = '新手注册红包【register_gift】';
                        break;
                    case 7:
                        $type = '分享红包【share_gift】';
                        break;
                    case 8:
                        $type = '签到【user_sign】';
                        break;
                    case 9:
                        $type = '充值【平台->图库 plat_recharge】';
                        break;
                    case 10:
                        $type = '提现【图库->平台 plat_withdraw】';
                        break;
                    case 11:
                        $type = '福利【'.(($v['welfare'] && $v['welfare']['name']) ? $v['welfare']['name'].'】' : 'user_welfare】');
                        break;
                    case 12:
                        $type = '充值【平台->图库 revoke_plat_recharge】【撤回】';
                        break;
                    case 13:
                        $type = '投注【'.(
                            $v['bet']['status'] == 0 ? '下单' :
                                ($v['bet']['status'] == -2 ? '已撤单' : '已开奖')
                            ).'】';
                        break;
                    case 14:
                        $type = '投注【'.($v['bet']['status'] == 0 ? '未开奖' : ($v['bet']['status'] == -1 ? '已开奖【未中】' : ($v['bet']['status'] == 1 ? '已开奖【中奖】' : ($v['bet']['status'] == -2 ? '已撤单' : '平局')))).'】';
                        break;
                    case 15:
                        $type = '投注【撤单】';
                        break;
                    case 16:
                        $type = '投注【系统撤回】';
                        break;
                    case 17:
                        $type = '提现审核【撤回】';
                        break;
                    case 18:
                        $type = '红包【聊天室】';
                        break;
                    case 19:
                        $type = '红包【五福红包】';
                        break;
                    case 20:
                        $reward = $v['reward'] ?? null;
                        if ($reward && $reward['type'] == 1) {
                            $rewardType = '发现';
                        } elseif ($reward && $reward['type'] == 2) {
                            $rewardType = '论坛';
                        } else {
                            $rewardType = '图解';
                        }
                        $type = '打赏【' . $rewardType . '】';
                        break;
                    case 21:
                        $reward = $v['posts'] ?? null;
                        if ($reward && $reward['type'] == 1) {
                            $rewardType = '论坛点赞';
                        } elseif ($reward && $reward['type'] == 2) {
                            $rewardType = '发现点赞';
                        } elseif ($reward && $reward['type'] == 3) {
                            $rewardType = '论坛阅读';
                        } else {
                            $rewardType = '发现阅读';
                        }
                        $type = '发帖【' . $rewardType . '】';
                        break;
                    case 22:
                        $type = '游戏【PG电子转入】';
                        break;
                    case 23:
                        $type = '游戏【PG电子转出】';
                        break;
                    case 24:
                        $type = '游戏【IMOne电子转入】';
                        break;
                    case 25:
                        $type = '游戏【IMOne电子转出】';
                        break;
                    case 26:
                        $type = '游戏【开元棋牌转入】';
                        break;
                    case 27:
                        $type = '游戏【开元棋牌转出】';
                        break;
                    case 28:
                        $type = '游戏【PG2电子转入】';
                        break;
                    case 29:
                        $type = '游戏【PG2电子转出】';
                        break;
                    case 30:
                        $type = '高手榜【支付】';
                        break;
                    case 31:
                        $type = '高手榜【收益】';
                        break;
                }
                $list['data'][$k]['type'] = $type;
            }
        }

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total']
        ]);
    }
}
