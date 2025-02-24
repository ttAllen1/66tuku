<?php

namespace Modules\Api\Services\game;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\Game;
use Modules\Api\Models\User;
use Modules\Api\Models\UserGame;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Utils\RedisLock;
use Modules\Common\Exceptions\ApiException;

class Pg2Service extends BaseApiService
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
            $config = AuthGameConfig::val(['pg2_api_domain', 'pg2_api_key']);
            if (!$config['pg2_api_domain']) {
                throw new Exception('error');
            }
            $parameter = array_merge($data, ['apikey' => $config['pg2_api_key']]);
            if (isset($parameter['externaltransactionid'])) {
                unset($parameter['externaltransactionid']);
            }
            if (isset($parameter['language'])) {
                unset($parameter['language']);
            }
            $sign = md5(http_build_query($parameter));
            $data['sign'] = $sign;
            $response = Http::asForm()->timeout(30)->post($config['pg2_api_domain'] . $path, $data);
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
                if (isset($result['errorcode'])) {
                    throw new Exception($result['error'], 501);
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
                Log::channel('pg2_Transfer_Out')->info($exception->getMessage());
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
            if (!$userGameInfo || !$userGameInfo->pg2_create) {
                $this->createUser($name);
            }
            if (!$userGameInfo) {
                UserGame::create([
                    'user_id' => request()->userinfo->id,
                    'pg2_create' => 1,
                    'last_recharge_type' => 5
                ]);
            } else {
                $userGame = UserGame::find($userGameInfo->id);
                if (!$userGameInfo->pg2_create) {
                    $userGame->pg2_create = 1;
                }
                $userGame->last_recharge_type = 5;
                $userGame->save();
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('pg2_Transfer_Out')->info('Account Create Error: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            $this->apiError('账号创建失败，请联系客服。');
        }

        // 转入金币
        try {
            DB::beginTransaction();
            $recharegAmount = User::query()->where('id', request()->userinfo->id)->lockForUpdate()->value('account_balance');
            $recharegAmount = floatval(intval($recharegAmount));
            if ($recharegAmount > 0) {
                $this->transferIn($name, $recharegAmount);
                User::query()->where('id', request()->userinfo->id)->decrement('account_balance', $recharegAmount);
                (new ActivityService())->modifyAccount(request()->userinfo->id, 28, $recharegAmount);
            }
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('pg2_Transfer_Out')->info('Amount Transfer Error: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            if ($exception->getMessage() == '此帐号不存在') {
                try {
                    $this->createUser($name);
                } catch (Exception $exception) {

                }
                $this->apiError('网络超时，请重试。');
            }
            $this->apiError('金币转入失败，请联系客服。');
        }

        // 获取游戏链接
        try {
            $data = $this->http('/game/gameOpen', [
                'playername' => AuthGameConfig::val('pg2_api_merchant').'_'.$name,
                'company' => 'PG',
                'game' => $parameter['gameId'],
                'mode' => 'real',
                'language' => 'zh',
            ]);
            RedisLock::unLock($lockKey);
            return $this->apiSuccess('成功', ['data' => $data['gameUrl']]);
        } catch (Exception $exception) {
            Log::channel('pg2_Transfer_Out')->info('Get Game Url: ' . $name . ' => ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            if ($exception->getMessage() == '帐号错误') {
                try {
                    $this->createUser($name);
                } catch (Exception $exception) {

                }
                $this->apiError('网络超时，请重试。');
            }
            $this->apiError($exception->getMessage());
        }
    }

    /**
     * 获取ky钱包余额
     * @param $name
     * @return array|mixed|string|null
     * @throws ApiException
     */
    public function getAmount($name)
    {
        return $this->http('/player/balance', [
            'playername' => AuthGameConfig::val('pg2_api_merchant').'_'.$name,
            'company' => 'PG',
        ]);
    }

    /**
     * 金额转出
     * @param $name
     * @return void
     */
    public function transferOut($name, $increment = true)
    {
        try {
            DB::beginTransaction();
            $getAmount = $this->getAmount($name);
            $amount = floatval(intval($getAmount['balance']));
            if ($amount > 0) {
                $this->http('/player/withdraw', [
                    'playername' => AuthGameConfig::val('pg2_api_merchant').'_'.$name,
                    'amount' => $amount,
                    'adminname' => AuthGameConfig::val('pg2_api_admin'),
                    'company' => 'PG',
                    'externaltransactionid' => $this->RandCreateOrderNumber(),
                ]);
                if ($increment) {
                    User::query()->where('id', request()->userinfo->id)->increment('account_balance', $amount);
                    (new ActivityService())->modifyAccount(request()->userinfo->id, 29, $amount);
                }
            }
            UserGame::query()->where('user_id', request()->userinfo->id)->update(['last_recharge_type' => 0]);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('pg2_Transfer_Out')->info('Transfer Out Error: ' . $name . ' => ' . $exception->getMessage());
        }
    }

    /**
     * 金额转入
     * @param string $name
     * @param float $amount
     * @return void
     * @throws ApiException
     */
    protected function transferIn(string $name, float $amount)
    {
        $this->http('/player/deposit', [
            'playername' => AuthGameConfig::val('pg2_api_merchant').'_'.$name,
            'amount' => $amount,
            'adminname' => AuthGameConfig::val('pg2_api_admin'),
            'company' => 'PG',
            'externaltransactionid' => $this->RandCreateOrderNumber(),
        ]);
    }

    /**
     * 防PG用户创建
     * @param string $name
     * @return array|mixed|string|null
     * @throws ApiException
     */
    protected function createUser(string $name)
    {
//        try {
            return $this->http('/player/create', [
                'playername' => AuthGameConfig::val('pg2_api_merchant').'_'.$name,
                'adminname' => AuthGameConfig::val('pg2_api_admin'),
                'company' => 'PG',
            ]);
//        } catch (Exception $exception) {
//            if ($exception->getMessage() != '此帐号已存在') {
//                throw new Exception($exception->getMessage(), $exception->getCode());
//            }
//        }
    }

}
