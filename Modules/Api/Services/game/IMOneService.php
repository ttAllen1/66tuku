<?php

namespace Modules\Api\Services\game;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\User;
use Modules\Api\Models\UserGame;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Utils\RedisLock;
use Modules\Common\Exceptions\ApiException;

class IMOneService extends BaseApiService
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
            $config = AuthGameConfig::val('imone_api_domian,imone_merchant_code');
            if (!$config) {
                throw new Exception('error');
            }
            list('imone_api_domian' => $domain, 'imone_merchant_code' => $mc) = $config;
            $data = array_merge([
                'MerchantCode' => $mc
            ], $data);
            $response = Http::timeout(3)
                ->post($domain . $path, $data);
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
                if ($result['Code'] != "0") {
                    throw new Exception($result['Message'], 501);
                }
                return $result;
            }
            throw new Exception('error');
        } catch (ConnectionException $exception) {
            $this->apiError('系统通信错误.');
        } catch (RequestException $exception) {
            $this->apiError('系统响应错误.');
        } catch (Exception $exception) {
            if ($exception->getCode() == 501) {
                Log::channel('IMOne_Transfer_Out')->info($exception->getMessage());
                $this->apiError($exception->getMessage());
            }
            $this->apiError('系统错误,请联系客服');
        }
    }

    /**
     * 获取跳转URL
     * @param $parameter
     * @return JsonResponse|void
     * @throws ApiException
     */
    public function getLaunchURLHTML()
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
            if (!$userGameInfo || !$userGameInfo->imone_create) {
                $this->createUser($name);
            }
            if (!$userGameInfo) {
                UserGame::create([
                    'user_id' => request()->userinfo->id,
                    'imone_create' => 1,
                    'last_recharge_type' => 2
                ]);
            } else {
                $userGame = UserGame::find($userGameInfo->id);
                if (!$userGameInfo->imone_create) {
                    $userGame->imone_create = 1;
                }
                $userGame->last_recharge_type = 2;
                $userGame->save();
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('IMOne_Transfer_Out')->info('Account Create Error: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            $this->apiError('账号创建失败，请联系客服。');
        }

        // 转入金币
        try {
            DB::beginTransaction();
            $recharegAmount = User::query()->where('id', request()->userinfo->id)->lockForUpdate()->value('account_balance');
            $recharegAmount = floatval(intval($recharegAmount));
            if ($recharegAmount > 0) {
                $this->transfer($name, $recharegAmount);
                User::query()->where('id', request()->userinfo->id)->decrement('account_balance', $recharegAmount);
                (new ActivityService())->modifyAccount(request()->userinfo->id, 24, $recharegAmount);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('IMOne_Transfer_Out')->info('Amount Transfer Error: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            $this->apiError('金币转入失败，请联系客服。');
        }

        try {
            $data = $this->http('/Game/NewLaunchMobileGame', [
                'PlayerId' => $name,
                'GameCode' => 'IMSB',
                'Language' => 'ZH-CN',
                'IpAddress' => $this->getIp(),
                'ProductWallet' => 301,
            ]);
            RedisLock::unLock($lockKey);
            return $this->apiSuccess('成功', ['data' => $data['GameUrl']]);
        } catch (Exception $exception) {
            Log::channel('IMOne_Transfer_Out')->info('Get Game Url: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            $this->apiError($exception->getMessage());
        }
    }

    /**
     * 获取IMOne钱包余额
     * @param $name
     * @return array|mixed|string|null
     * @throws ApiException
     */
    public function getAmount($name)
    {
        return $this->http('/Player/GetBalance', [
            'PlayerId' => $name,
            'ProductWallet' => 301,
        ]);
    }

    /**
     * 转入IMOne
     * @param string $name
     * @param float $amount
     * @return void
     * @throws ApiException
     */
    protected function transfer(string $name, float $amount)
    {
        $this->http('/Transaction/PerformTransfer', [
            'PlayerId' => $name,
            'ProductWallet' => 301,
            'TransactionId' => $this->RandCreateOrderNumber(),
            'Amount' => $amount,
        ]);
    }

    /**
     * IMOne钱包余额转出
     * @param $name
     * @return void
     */
    public function transferOut($name, $increment = true)
    {
        try {
            DB::beginTransaction();
            $getAmount = $this->getAmount($name);
            $amount = floatval(intval($getAmount['Balance']));
            if ($amount > 0) {
                $this->http('/Transaction/PerformTransfer', [
                    'PlayerId' => $name,
                    'ProductWallet' => 301,
                    'TransactionId' => $this->RandCreateOrderNumber(),
                    'Amount' => $amount * -1
                ]);
                if ($increment) {
                    User::query()->where('id', request()->userinfo->id)->increment('account_balance', $amount);
                    (new ActivityService())->modifyAccount(request()->userinfo->id, 25, $amount);
                }
            }
            UserGame::query()->where('user_id', request()->userinfo->id)->update(['last_recharge_type' => 0]);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('IMOne_Transfer_Out')->info('Transfer Out Error: ' . $name . ' => ' . $exception->getMessage());
        }
    }

    /**
     * IMOne用户创建
     * @param string $name
     * @return void
     * @throws ApiException
     */
    protected function createUser(string $name)
    {
        $this->http('/Player/Register', [
            'PlayerId' => $name,
            'Currency' => 'CNY',
            'Password' => '84bw71a834hsdfdz',
        ]);
    }

}
