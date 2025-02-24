<?php

namespace Modules\Api\Services\game;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Filesystem\Cache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\GameTransfer;
use Modules\Api\Models\User;
use Modules\Api\Models\UserGame;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Utils\RedisLock;
use Modules\Common\Exceptions\ApiException;

class KyService extends BaseApiService
{
    /**
     * 第三发平台网络通信
     * @param string $path
     * @param array $data
     * @param bool $jsonDecode
     * @return array|mixed|string|void
     * @throws ApiException
     */
    protected function http(string $path, array $data, bool $jsonDecode = true)
    {
        try
        {
            $config = AuthGameConfig::val('ky_agent,ky_deskey,ky_domain,ky_md5key');
            if (!$config) {
                throw new Exception('error');
            }
            list('ky_agent' => $agent, 'ky_deskey' => $deskey, 'ky_domain' => $domain, 'ky_md5key' => $md5key) = $config;
            $queryData = http_build_query($data);
            $microtime = $this->microtime_float();
            $submitParameter = [
                'agent' => $agent,
                'timestamp' => $microtime,
                'param' => openssl_encrypt($queryData, 'aes-128-ecb', $deskey),
                'key' => md5($agent.$microtime.$md5key)
            ];
            if (strpos($path, '?') === false) {
                $path = $path . '?' . http_build_query($submitParameter);
            } else {
                $path = $path . '&' . http_build_query($submitParameter);
            }
            $response = Http::timeout(15)->get($domain . $path);
            if (!$jsonDecode) {
                return $response->body();
            }
            if ($response->failed()) {
                throw new Exception('error');
            }
            if ($response->successful()) {
                $result = $response->json();
                if (empty($result)) {
                    throw new Exception('error');
                }
                if ($result['d']['code'] != "0") {
                    throw new Exception($result['d']['code'], 501);
                }
                return $result['d'];
            }
            throw new Exception('error');
        } catch (ConnectionException $exception) {
            $this->apiError('系统通信错误.');
        } catch (RequestException $exception) {
            $this->apiError('系统响应错误.');
        } catch (Exception $exception) {
            if ($exception->getCode() == 501) {
                Log::channel('ky_Transfer_Out')->info($exception->getMessage());
                $this->apiError($exception->getMessage());
            }
            $this->apiError('系统错误,请联系客服');
        }
    }

    /**
     * 获取跳转URL
     */
    public function login($parameter)
    {
//        $repeatClick = Redis::get('other_game_uid_' . request()->userinfo->id);
//        if ($repeatClick) {
//            $this->apiError('请求过快，请放慢点击速度。');
//        }
//        Redis::setex('other_game_uid_' . request()->userinfo->id, 2, 1);

        if ($this->userinfo()->is_balance_freeze == 1) {
            $this->apiError('账号已被冻结。');
        }

        $lockKey = 'other_game_lock_uid_' . request()->userinfo->id;
        if (!RedisLock::lock($lockKey, 15)) {
            $this->apiError('系统未响应，请联系客服');
        }

        $name = request()->ky_linecode . request()->userinfo->account_name;

        // 创建游戏账号
        try {
            DB::beginTransaction();
            $userGameInfo = UserGame::query()->where('user_id', request()->userinfo->id)->first();
            $data = $this->createUser($name, 0, $parameter['gameId']);
            if (!$userGameInfo) {
                UserGame::create([
                    'user_id' => request()->userinfo->id,
                    'ky_create' => 1,
                    'last_recharge_type' => 3
                ]);
            } else {
                $userGame = UserGame::find($userGameInfo->id);
                if (!$userGameInfo->ky_create) {
                    $userGame->ky_create = 1;
                }
                $userGame->last_recharge_type = 3;
                $userGame->save();
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('ky_Transfer_Out')->info('Account Create Error: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            $this->apiError('账号创建失败，请联系客服。');
        }

        // 转入金币
        $orderNo = AuthGameConfig::val('ky_agent').date('YmdHis').$this->get_millisecond().$name;
        try {
            DB::beginTransaction();
            $recharegAmount = User::query()->where('id', request()->userinfo->id)->lockForUpdate()->value('account_balance');
            $recharegAmount = floatval(intval($recharegAmount));
            if ($recharegAmount > 0) {
                $this->transferIn($name, $recharegAmount, $orderNo);
                User::query()->where('id', request()->userinfo->id)->decrement('account_balance', $recharegAmount);
                (new ActivityService())->modifyAccount(request()->userinfo->id, 26, $recharegAmount);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            GameTransfer::query()->insert([
                'user_id' => request()->userinfo->id,
                'amount' => $recharegAmount,
                'account' => $name,
                'order_no' => $orderNo,
                'type' => 'transferIn',
            ]);
            Log::channel('ky_Transfer_Out')->info('Amount TransferIn Error: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
        }
        RedisLock::unLock($lockKey);
        return $this->apiSuccess('成功', ['data' => $data['url']]);

    }

    /**
     * 获取ky钱包余额
     * @param $name
     * @return array|mixed|string|null
     * @throws ApiException
     */
    public function getAmount($name)
    {
        return $this->http('/channelHandle', [
            's' => 1,
            'account' => $name,
        ]);
    }

    /**
     * ky钱包余额转入
     * @param string $name
     * @param float $amount
     * @param string $orderNo
     * @return array|mixed|string|null
     * @throws ApiException
     */
    public function transferIn(string $name, float $amount, string $orderNo)
    {
        return $this->http('/channelHandle', [
            's' => 2,
            'account' => $name,
            'money' => $amount,
            'orderid' => $orderNo,
        ]);
    }

    /**
     * @param string $name
     * @param float $amount
     * @param string $orderNo
     * @return array|mixed|string|null
     * @throws ApiException
     */
    public function checkTransferOut(string $name, float $amount, string $orderNo)
    {
        return $this->http('/channelHandle', [
            's' => 3,
            'account' => $name,
            'money' => $amount,
            'orderid' => $orderNo,
        ]);
    }

    /**
     * ky钱包余额转出
     * @param $name
     * @param $increment
     * @return void
     */
    public function transferOut($name, $increment = true)
    {
        $orderNo = AuthGameConfig::val('ky_agent').date('YmdHis').$this->get_millisecond().$name;
        try {
            DB::beginTransaction();
            $getAmount = $this->getAmount($name);
            $amount = floatval(intval($getAmount['money']));
            if ($amount > 0) {
                $this->http('/channelHandle', [
                    's' => 3,
                    'account' => $name,
                    'money' => $amount,
                    'orderid' => $orderNo,
                ]);
                if ($increment) {
                    User::query()->where('id', request()->userinfo->id)->increment('account_balance', $amount);
                    (new ActivityService())->modifyAccount(request()->userinfo->id, 27, $amount);
                }
            }
            UserGame::query()->where('user_id', request()->userinfo->id)->update(['last_recharge_type' => 0]);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            GameTransfer::query()->insert([
                'user_id' => request()->userinfo->id,
                'amount' => $amount,
                'account' => $name,
                'order_no' => $orderNo,
                'type' => 'transferOut',
            ]);
            Log::channel('ky_Transfer_Out')->info('Amount TransferOut Error: ' . $name . ' => ' . $exception->getMessage());
        }
    }

    /**
     * ky用户创建
     * @param string $name
     * @param float $amount
     * @return array|mixed|string|null
     * @throws ApiException
     */
    protected function createUser(string $name, float $amount, int $kindID)
    {
        $amount = 0;
        return $this->http('/channelHandle', [
            's' => 0,
            'account' => $name,
            'money' => $amount,
            'orderid' => AuthGameConfig::val('ky_agent').date('YmdHis').$this->get_millisecond().$name,
            'ip' => $this->getIp(),
            'lineCode' => AuthGameConfig::val('ky_linecode'),
            'KindID' => $kindID,
        ]);
    }

}
