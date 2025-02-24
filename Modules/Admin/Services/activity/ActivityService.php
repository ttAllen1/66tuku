<?php
/**
 * 活动管理服务
 * @Description
 */

namespace Modules\Admin\Services\activity;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Models\Level;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\UserJoinActivity;

class ActivityService extends BaseApiService
{
    /**
     * 活动配置
     * @param array $data
     * @return JsonResponse
     */
    public function config(array $data): JsonResponse
    {
        $configs = AuthActivityConfig::query()
            ->get()
            ->toArray();
        $list = [];
        foreach ($configs as $config) {
            if ($config['k'] == 'signin_day_species' || $config['k'] == "novice_register_species" || $config['k'] == "app_first_species" || $config['k'] == "forecast_bets_money") {
                $list[$config['k']] = json_decode($config['v'], true);
            } else if ( in_array($config['k'], ['forecast_bet_win_type', 'five_bliss_open', 'zan_area_open', 'read_area_open', 'zan_random_open', 'fav_random_open', 'discover_reward_open', 'discuss_reward_open', 'diagram_reward_open', 'discover_post_open', 'discuss_post_open'])){
                $list[$config['k']] = (int)$config['v'];
            } else if ( in_array($config['k'], ['five_bliss_gold_min', 'five_bliss_gold_max', 'zan_num_area', 'read_num_area', 'reward_gold_section'])){
                if (in_array($config['k'], ['five_bliss_gold_max', 'zan_num_area', 'read_num_area']) ) {
                    $configV = unserialize($config['v']);
                    foreach($configV as $k => $item) {
                        $list[$config['k']][$k] = implode('-', $item).PHP_EOL;
                    }
                    $list[$config['k']] = implode('', $list[$config['k']]);
                } else {
                    $list[$config['k']] = unserialize($config['v']);
                }
            }else {
                $list[$config['k']] = $config['v'];
            }
        }
        $list['zan_min_user_level'] = Level::query()->where('scores', $list['zan_min_user_level'])->value('id');
        $list['read_min_user_level'] = Level::query()->where('scores', $list['read_min_user_level'])->value('id');

        $user_levels = Level::query()->where('status', 1)->select(['level_name', 'id'])->get()->toArray();

        return $this->apiSuccess('',[
            'data'          =>$list,
            'user_levels'   => $user_levels
        ]);
    }

    /**
     * 修改提交
     * @param array $data
     * @return JsonResponse
     */
    public function config_update(array $data): JsonResponse
    {
        $data['signin_day_species'] = json_encode($data['signin_day_species']);
        $data['novice_register_species'] = json_encode($data['novice_register_species']);
        $data['app_first_species'] = json_encode($data['app_first_species']);
        $data['forecast_bets_money'] = json_encode($data['forecast_bets_money']);
        $data['five_bliss_gold_min'] = serialize($data['five_bliss_gold_min']);
        $data['reward_gold_section'] = serialize($data['reward_gold_section']);
        if ($data['five_bliss_gold_max']) {
            $five_bliss_gold_max = $data['five_bliss_gold_max'];
            $five_bliss_gold_max = explode(PHP_EOL, $five_bliss_gold_max);
            foreach($five_bliss_gold_max as $k => $v) {
                $five_bliss_gold_max[$k] = explode('-', $v);
            }
            $data['five_bliss_gold_max'] = serialize($five_bliss_gold_max);
        }
        if ($data['zan_num_area']) {
            $zan_num_area = $data['zan_num_area'];
            $zan_num_area = explode(PHP_EOL, $zan_num_area);
            foreach($zan_num_area as $k => $v) {
                $zan_num_area[$k] = explode('-', $v);
            }
            $data['zan_num_area'] = serialize($zan_num_area);
        }
        if ($data['read_num_area']) {
            $read_num_area = $data['read_num_area'];
            $read_num_area = explode(PHP_EOL, $read_num_area);
            foreach($read_num_area as $k => $v) {
                $read_num_area[$k] = explode('-', $v);
            }
            $data['read_num_area'] = serialize($read_num_area);
        }
        $data['read_min_user_level'] = DB::table('levels')->where('id', $data['read_min_user_level'])->value('scores');
        $data['zan_min_user_level'] = DB::table('levels')->where('id', $data['zan_min_user_level'])->value('scores');

        foreach ($data as $key => $val){
            AuthActivityConfig::query()->updateOrInsert(
                ['k' => $key],
                ['v' => $val]
            );
        }
        Redis::set('forecast_bet_win_type', (int)$data['forecast_bet_win_type']);

        return $this->apiSuccess();
    }

    /**
     * 五福活动
     * @param $params
     * @return JsonResponse
     */
    public function five_index($params): JsonResponse
    {
        $account_name = $params['account_name'] ?? '';
        $is_finish = $params['is_finish'] ?? -1;
        $is_receive = $params['is_receive'] ?? -1;
        $userIds = [];
        $flag = true;
        $configs = AuthActivityConfig::val(['five_bliss_receive_time', 'five_bliss_show_end', 'five_bliss_start', 'five_bliss_end']);
        if ($account_name) {
            $userIds = DB::table('users')->where('account_name', 'like', '%'.$account_name.'%')->pluck('id')->toArray();
        }
        if ($is_receive != -1) {
            $userRecIds = DB::table('user_five_receives')->whereBetween('created_at', [$configs['five_bliss_receive_time'], $configs['five_bliss_show_end']])->pluck('user_id')->toArray();
            if ($is_receive==1) { // 已领取
                if ($userRecIds) {
                    if ($userIds) { // 排除不存在的id
                        $userIds = array_intersect($userIds, $userRecIds);
                        if (!$userIds) $userIds = [-1];
                    } else {
                        $userIds = $userRecIds;
                    }
                }
            } else { // 未领取
                if ($userRecIds) {
                    if ($userIds) { // 排除不存在的id
                        $userIds = array_diff($userIds, $userRecIds);
                        if (!$userIds) $userIds = [-1];
                    } else {
                        $userIds = $userRecIds;
                        $flag = false;
                    }
                }
            }
        }
        $list = UserJoinActivity::query()
//            ->whereBetween('created_at', [$configs['five_bliss_start'], $configs['five_bliss_end']])
            ->latest()
            ->when($userIds && $flag, function ($query) use ($userIds) {
                $query->whereIn('user_id', $userIds);
            })
            ->when($is_finish !=-1, function ($query) use ($is_finish) {
                $query->where('is_finish', $is_finish);
            })
            ->when($is_receive !=-1 && $flag, function ($query) use ($userIds) {
                $query->whereIn('user_id', $userIds);
            })
            ->when($is_receive !=-1 && !$flag, function ($query) use ($userIds) {
                $query->whereNotIn('user_id', $userIds);
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'account_name']);
            }, 'user_bliss', 'user_receive'])
            ->paginate($params['limit'])->toArray();
        if (!$list) {
            return $this->apiSuccess('',[
                'list'      => [],
                'total'     => 0,
            ]);
        }
        foreach($list['data'] as $k => $v) {
            if ($v['five_id'] == 1) {
                $list['data'][$k]['complete_schedule'] = '100%';
            } else if ($v['five_id'] == 2) {
                $list['data'][$k]['complete_schedule'] = '100%';
            } else if ($v['five_id'] == 3) {
                $list['data'][$k]['complete_schedule'] = '100%';
            } else if ($v['five_id'] == 4) {
                if ($v['is_finish']==1) {
                    $list['data'][$k]['complete_schedule'] = '100%';
                } else {
                    $list['data'][$k]['complete_schedule'] = ($v['complete_schedule']/5*100).'%';
                }
            } else if ($v['five_id'] == 5) {
                if ($v['is_finish']==1) {
                    $list['data'][$k]['complete_schedule'] = '100%';
                } else {
                    $list['data'][$k]['complete_schedule'] = ($v['complete_schedule']/5*100).'%';
                }
            } else if ($v['five_id'] == 6) {
                if ($v['is_finish']==1) {
                    $list['data'][$k]['complete_schedule'] = '100%';
                } else {
                    $list['data'][$k]['complete_schedule'] = ($v['complete_schedule']/5*100).'%';
                }
            } else {
                $list['data'][$k]['complete_schedule'] = '100%';
            }
        }
        $this->sortArrayByField($list['data'], 'user_id');
        $counts = array_count_values(array_column($list['data'], 'user_id'));
        foreach($list['data'] as $k => $v) {
            if ($k==0) {
                $list['data'][$k]['length'] = $counts[$v['user_id']];
            } else{
                if ($list['data'][$k-1]['user_id'] == $v['user_id']) {
                    $list['data'][$k]['length'] = 0;
                } else {
                    $list['data'][$k]['length'] = $counts[$v['user_id']];
                }
            }
        }
        // 总参与人
        $join_counts = DB::table('user_join_activities')->distinct('user_id')->count();
        // 完成人数
        $finish_counts = DB::table('user_join_activities')->where('is_finish', 1)->distinct('user_id')->count();
        // 领取总金额
        $receive_money = DB::table('user_five_receives')->whereBetween('created_at', [$configs['five_bliss_start'], $configs['five_bliss_show_end']])->sum('money');
        // 领取总人数
        $receive_counts = DB::table('user_five_receives')->whereBetween('created_at', [$configs['five_bliss_start'], $configs['five_bliss_show_end']])->distinct('user_id')->count();
        // 全部完成人数
        $final_finish_counts = UserJoinActivity::query()
            ->where('is_finish', 1)
            ->where([
                ['created_at', '>=', $configs['five_bliss_start']],
                ['created_at', '<', $configs['five_bliss_end']],
            ])
            ->groupBy('user_id')
            ->havingRaw('count(user_id) = 7')
            ->count();

        return $this->apiSuccess('',[
            'list'                => $list['data'],
            'total'               => $list['total'],
            'join_counts'         => $join_counts,
            'finish_counts'       => $finish_counts,
            'receive_money'       => $receive_money,
            'receive_counts'      => $receive_counts,
            'final_finish_counts' => $final_finish_counts,
        ]);
    }

}

// 返点系数：1980-2000 默认1980
// 决定中奖赔率
