<?php

namespace Modules\Admin\Services\chat;

use Faker\Factory;
use GatewayClient\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Admin\Models\ChatRoom;
use Modules\Admin\Models\User;
use Modules\Admin\Models\UserChat;
use Modules\Admin\Services\BaseApiService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class ChatService extends BaseApiService
{
    public function __construct()
    {
        parent::__construct();
//        Gateway::$registerAddress = '127.0.0.1:1238';
    }

    public function list($data)
    {
        $account_name = $data['account_name'] ?? '';
        $userIds = [];
        if ($account_name) {
            $userIds = User::query()
                ->where('account_name', 'like', '%'.$account_name.'%')
                ->get(['id'])->toArray();
        }
        $chats = UserChat::query()
            ->when($data['room_id'], function($query) use ($data) {
                $query->where('room_id', $data['room_id']);
            })
            ->when($data['status']!=-1, function($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->when($data['keywords'], function($query) use ($data) {
                $query->where('message', 'like', '%'.$data['keywords'].'%');
            })
            ->when($userIds, function($query) use ($userIds) {
                $query->whereIn('from_user_id', $userIds);
            })
            ->latest()
            ->with(['room', 'user'=>function($query) {
                $query->select(['id', 'is_forbid_speak', 'account_name']);
            }])
            ->paginate($data['limit'])
            ->toArray();

        $chatRoom = ChatRoom::query()->select(['name', 'id'])->get();

        return $this->apiSuccess('',[
            'list'          => $chats['data'],
            'total'         => $chats['total'],
            'chatRoom'      => $chatRoom
        ]);
    }

    public function delete($data)
    {
        try {
            $uuids = is_array($data['uuid']) ? $data['uuid'] : [$data['uuid']];
            (new \Modules\Api\Services\room\RoomService())->delete(['room_id' => $data['room_id'], 'uuid' => $uuids]);
        } catch (CustomException $e) {
            return $this->apiSuccess('删除失败');
        }
        return $this->apiSuccess('删除成功');
    }

    public function check($data)
    {
        try {
            $uuids = is_array($data['uuid']) ? $data['uuid'] : [$data['uuid']];
            (new \Modules\Api\Services\room\RoomService())->check(['room_id' => $data['room_id'], 'uuid' => $uuids]);
        } catch (CustomException $e) {
            return $this->apiSuccess('修改失败');
        }
        return $this->apiSuccess('修改成功');
    }

    /**
     * 机器人列表
     * @param $data
     * @return JsonResponse
     */
    public function chat_robot_list($data): JsonResponse
    {
        $res = User::query()->where('is_chat', 1)->latest()->select(['id', 'account_name', 'nickname', 'register_at'])->paginate($data['limit']);
        return $this->apiSuccess('', $res->toArray());
    }

    /**
     * 机器人创建
     * @param $data
     * @return JsonResponse
     * @throws ApiException
     */
    public function chat_robot_store($data): JsonResponse
    {
//        dd(Factory::create('zh_CN')->name);
        try{
//            DB::beginTransaction();
            $pw = $data['pwd'];
            $number = (int)$data['number'];
            $accountNameMaxNumber = 0;
            $nicknameMaxNumber = 0;
            $maxNumber = User::query()
                ->where('is_chat', 1)
                ->where('system', 2)
                ->where('account_name', 'like', '%'.$data['account_name'].'%')
                ->latest()
                ->orderBy('id', 'desc')
                ->first(['account_name', 'nickname']);
            if ($maxNumber) {
                $accountNameMaxNumber = str_replace($data['account_name'], '', $maxNumber['account_name']);
                $nicknameMaxNumber = str_replace(['49图库_'], '', $maxNumber['nickname']);
            }

//            $number += $nicknameMaxNumber;
//            $ii = $nicknameMaxNumber+1;
            $date = date('Y-m-d H:i:s');
            $isExist = DB::table('users')->where('register_at', $date)->where('account_name', 'like', '%'.$data['account_name'].'%')->exists();
            if ($isExist) {
                sleep(1);
            }
            $dbData = [];
            for ($i = 1; $i <= $number; $i++) {
                $dbData[] = [
                    'account_name'      => $data['account_name'] . ($accountNameMaxNumber+1),
                    'mobile'            => '',
                    'nickname'          => rand(1,10) > 7 ? Factory::create('zh_CN')->name : '49图库_' . rand(100000, 999999),
                    'chat_user'         => $this->str_rand(7),
                    'password'          => bcrypt($pw),
                    'chat_pwd'          => md5($pw),
                    'invite_code'       => $this->randString(),
                    'avatar'            => '/upload/images/20231119/pp3762zWBp25yOMvHlatvXFkgdVZR382TwklkYje.jpg',
                    'new_avatar'        => '/upload/images/20231119/pp3762zWBp25yOMvHlatvXFkgdVZR382TwklkYje.jpg',
                    'register_ip'       => 0,
                    'register_area'     => '',
                    'register_at'       => $date,
                    'created_at'        => $date,
                    'last_login_at'     => $date,
                    'last_login_ip'     => 0,
                    'last_login_area'   => '',
                    'last_login_device' => 4,
                    'is_online'         => 1,
                    'avatar_is_check'   => 1,
                    'is_chat'           => 1,
                    'register_device'   => 1,
                    'system'            => 2,
                ];
                $accountNameMaxNumber += 1;
            }
            if (!$dbData) {
                throw new CustomException(['message'=>'请刷新重试']);
            }
            DB::table('users')->insert($dbData);
            $ids = DB::table('users')->where('register_at', $date)->where('account_name', 'like', '%'.$data['account_name'].'%')->pluck('id')->toArray();
            $gData = [];
            foreach ($ids as $k => $id) {
                $gData[$k]['user_id'] = $id;
                $gData[$k]['group_id'] = 4;
                $gData[$k]['created_at'] = $date;
            }
            DB::table('user_groups')->insert($gData);
            return $this->apiSuccess('创建机器人成功');
        }catch (\Exception $exception) {
            return $this->apiError($exception->getMessage());
        }
    }

    /**
     * 保存智能配置
     * @return JsonResponse
     * @throws ApiException
     */
    public function chat_smart_list(): JsonResponse
    {
        $res = Redis::get('chat_smart');
        if (!$res) {
            return $this->apiSuccess();
        }
        return $this->apiSuccess('', json_decode($res, true));
    }

    /**
     * 保存智能配置
     * @param $data
     * @return JsonResponse
     */
    public function chat_smart_store($data): JsonResponse
    {
        Redis::set('chat_smart', json_encode($data));
        return $this->apiSuccess();
    }

}
