<?php
namespace Modules\Admin\Services\user;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\StationMsg;
use Modules\Admin\Models\UserMessage;
use Modules\Admin\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;
use Modules\Common\Models\UserWelfare;

class UserWelfareService extends BaseApiService
{
    /**
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $list = UserWelfare::query()
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['user_name'], function($query) use ($data) {
                $query->whereHas('user', function($query) use ($data) {
                    $query->where('account_name', 'like', '%'.$data['user_name'].'%');
                });
//                ->orderBy(DB::raw("CASE WHEN user.account_name = '" . $data['user_name'] . "' THEN 1 ELSE 2 END"));
            })
            ->when($data['welfare_name'], function($query) use ($data) {
                $query->where('name', $data['welfare_name']);
            })
            ->with(['user'=>function($query) {
                $query->select(['id', 'account_name']);
            }, 'msg'=>function($query) {
                $query->where('status', 1)->select(['id', 'title', 'content']);
            }])
            ->latest()
            ->paginate($data['limit'])->toArray();

        if ($list['data']) {
            foreach($list['data'] as $k => $v) {
                if ($v['valid_receive_date'][0] == 0) {
                    $list['data'][$k]['is_limit_time'] = 0;
                } else {
                    $list['data'][$k]['is_limit_time'] = 1;
                }
                if ($v['send_msg_id']) {
                    $list['data'][$k]['is_send_msg'] = 1;
                } else {
                    $list['data'][$k]['is_send_msg'] = 0;
                    $list['data'][$k]['msg']['title'] = '';
                    $list['data'][$k]['msg']['content'] = '';
                }
            }
        }

        $uniqueNames = DB::table('user_welfares')
            ->select('name')
            ->distinct()
            ->pluck('name');

        return $this->apiSuccess('',[
            'list'          => $list['data'],
            'total'         => $list['total'],
            'uniqueNames'   => $uniqueNames
        ]);
    }

    /**
     * 创建会员福利
     * @param $params
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function store($params): JsonResponse
    {
        $params = $this->operate($params);
        $params['user_id'] = is_array($params['user_id']) ? $params['user_id'] : [$params['user_id']];
        $params['order_num'] = 'FL'.$this->setTradeNo();

        $data = [];
        foreach($params['user_id'] as $k => $v) {
            $data[$k]['user_id'] = $v;
            $data[$k]['name'] = $params['name'];
            $data[$k]['order_num'] = $params['order_num'];
            $data[$k]['is_random'] = $params['is_random'];
            $data[$k]['random_money'] = $params['random_money'];
            $data[$k]['really_random_money'] = $params['really_random_money'];
            $data[$k]['really_money'] = $params['really_money'];
            $data[$k]['valid_receive_date'] = $params['valid_receive_date'];
            $data[$k]['created_at'] = date('Y-m-d H:i:s');
        }
//        dd($data, $params);
        try{
            // 发送站内消息
            if ($params['is_send_msg'] ==1) {
                $msg['title']       = $params['msg']['title'];
                $msg['content']     = $params['msg']['content'];
                $msg['type']        = 2;
                $msg['appurtenant'] = 2;
                $msg['created_at']  = date('Y-m-d H:i:s');
                $msgId = StationMsg::query()->insertGetId($msg);
                $userMsg = [];
                foreach($params['user_id'] as $k => $v) {
                    $userMsg[$k]['user_id'] = $v;
                    $userMsg[$k]['msg_id'] = $msgId;
                    $data[$k]['send_msg_id'] = $msgId;
                }
                UserMessage::query()->insert($userMsg);
            }
            UserWelfare::query()->insert($data);

            return $this->apiSuccess('创建成功');
        }catch (\Exception $exception) {
            return $this->apiError('创建失败');
        }
    }

    /**
     * @param $id
     * @param array $data
     * @return JsonResponse|null
     * @throws CustomException
     */
    public function update($id, array $data): ?JsonResponse
    {
        $id = is_array($id) ? $id : [$id];
        if ( $data['status'] ==1 ) {
            throw new CustomException(['message'=>'此状态禁止编辑']);
        }
        $data = $this->operate($data);
        unset($data['is_limit_time']);

        // 发送站内消息
        if ($data['is_send_msg'] ==1) {
            $msgArr = json_decode($data['msg'], true);
            $msg['title']       = $msgArr['title'];
            $msg['content']     = $msgArr['content'];
            $msg['type']        = 2;
            $msg['appurtenant'] = 2;
            $msg['updated_at']  = date('Y-m-d H:i:s');
            StationMsg::query()->where('id', $msgArr['id'])->update($msg);

        }
        unset($data['msg']);
        unset($data['is_send_msg']);
        return $this->commonUpdate(UserWelfare::query(), $id, $data);
    }

    /**
     * @param $params
     * @return array
     * @throws CustomException
     */
    protected function operate($params): array
    {
        if ($params['is_limit_time'] == 0) {
            $params['valid_receive_date'][0] = 0;
            $params['valid_receive_date'][1] = 0;
        } else {
            $params['valid_receive_date'] = [Carbon::create($params['valid_receive_date'][0])->format("Y-m-d"), Carbon::create($params['valid_receive_date'][1])->format("Y-m-d")];
        }
        $params['valid_receive_date'] = json_encode($params['valid_receive_date']);
        if ($params['is_random'] == 1) { // 随机
            if ( !is_array($params['random_money']) || !is_array($params['really_random_money'])) {
                throw new CustomException(['message'=>'随机金额应为数组']);
            }
            $params['really_money'] = rand($params['really_random_money'][0], $params['really_random_money'][1]);
        } else {    // 固定
//            $params['random_money'][1] = $params['random_money'][0];
            $params['really_random_money'] = $params['random_money'] = [];
//            $params['really_random_money'][1] = $params['really_random_money'][0] = $params['random_money'][0];
//            $params['really_money'] = $params['random_money'][0];
        }
        $params['random_money'] = json_encode($params['random_money']);
        $params['really_random_money'] = json_encode($params['really_random_money']);

        return $params;
    }

}
