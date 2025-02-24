<?php

namespace Modules\Api\Services\game;

use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\User;
use Modules\Api\Models\UserGame;
use Modules\Api\Services\activity\ActivityService;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Utils\RedisLock;
use Modules\Common\Exceptions\ApiException;

class PgService extends BaseApiService
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
            if (strpos($path, '?') === false) {
                $path = $path . '?trace_id=' . Str::Uuid();
            } else {
                $path = $path . '&trace_id=' . Str::Uuid();
            }
            $config = AuthGameConfig::val('pg_api_domain,pg_operator_token,pg_secret_key,pg_salt');
            if (!$config) {
                throw new Exception('error');
            }
            list('pg_api_domain' => $domain, 'pg_operator_token' => $ot, 'pg_secret_key' => $sk, 'pg_salt' => $salt) = $config;
            $data = array_merge([
                'operator_token' => $ot,
                'secret_key' => $sk
            ], $data);
            $sha256 = hash('sha256', http_build_query($data));
            $host = parse_url($domain)['host'];
            $xdate = date('Ymd');
            $hsx = $host.$sha256.$xdate;
            $sign = hash_hmac('sha256', $hsx, $salt);
            $response = Http::asForm()
                ->timeout(3)
                ->withHeaders([
                    'x-date' => $xdate,
                    'x-content-sha256' => $sha256,
                    'Authorization' => 'PWS-HMAC-SHA256 Credential='.$xdate.'/'.$ot.'/pws/v1,SignedHeaders=host;x-content-sha256;x-date,Signature='.$sign,
                ])
                ->post($domain . $path, $data);
            if (!$jsonDecode) {
                return $response->body();
            }
            if ($response->failed()) {
                throw new Exception(json_encode($response->json()['errors']), 501);
            }
            if ($response->successful()) {
                $result = $response->json();
                if (empty($result)) {
                    throw new Exception('error');
                }
                if ($result['error'] != null) {
                    throw new Exception($result['error']['message'], 501);
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
                Log::channel('Pg_Transfer_Out')->info($exception->getMessage());
                $this->apiError($exception->getMessage());
            }
            $this->apiError('系统错误.');
        }
    }

    /**
     * 第三方session验证
     * @param $parameter
     * @return array
     */
    public function verifySession($parameter)
    {
        try {
            $error = null;
            $config = AuthGameConfig::val('pg_operator_token,pg_secret_key');
            list('pg_operator_token' => $ot, 'pg_secret_key' => $sk) = $config;
            if ($parameter['operator_token'] != $ot || $parameter['secret_key'] != $sk) {
                throw new Exception('内部服务器错误', 1200);
            }
            $userId = UserGame::query()->where('pg_ops', $parameter['operator_player_session'])->value('user_id');
            if (!$userId) {
                throw new Exception('内部服务器错误', 1200);
            }
            $user = User::query()->where('id', $userId)->select(['account_name', 'nickname'])->first();
            if (!$user) {
                throw new Exception('内部服务器错误', 1200);
            }
            return [
                "data" => [
                    "player_name" => AuthGameConfig::val('ky_linecode') . $user->account_name,
                    "nickname" => $user->nickname,
                    "currency" => "CNY"
                ],
                "error" => $error
            ];
        } catch (Exception $exception) {
            return [
                "data" => [
                ],
                "error" => $exception->getCode()
            ];
        }
    }

    /**
     * 获取跳转HTML
     * @param $parameter
     * @return JsonResponse|void
     * @throws ApiException
     */
    public function getLaunchURLHTML($parameter)
    {

        if ($this->userinfo()->is_balance_freeze == 1) {
            $this->apiError('账号已被冻结。');
        }

        $lockKey = 'other_game_lock_uid_' . request()->userinfo->id;

        if (!RedisLock::lock($lockKey, 15)) {
            $this->apiError('系统响应错误，请联系客服。');
        }

        $name = request()->ky_linecode . request()->userinfo->account_name;

        try {
            DB::beginTransaction();
            $ops = urlencode(Str::Uuid());
            $recharge = false;
            $recharegAmount = User::query()->where('id', request()->userinfo->id)->lockForUpdate()->value('account_balance');
            $recharegAmount = floatval(intval($recharegAmount));
            $userGameInfo = UserGame::query()->where('user_id', request()->userinfo->id)->first();
            if (!$userGameInfo || !$userGameInfo->pg_create) {
                $this->createUser($name, request()->userinfo->nickname);
            }

            if ($recharegAmount > 0) {
                $this->transferIn($name, $recharegAmount);
                $recharge = true;
                User::query()->where('id', request()->userinfo->id)->decrement('account_balance', $recharegAmount);
                (new ActivityService())->modifyAccount(request()->userinfo->id, 22, $recharegAmount);
            }

            if (!$userGameInfo) {
                UserGame::create([
                    'user_id' => request()->userinfo->id,
                    'pg_create' => 1,
                    'pg_ops' => $ops,
                    'last_recharge_type' => 1,
                ]);
            } else if ($recharge || !$userGameInfo->pg_create) {
                $userGame = UserGame::find($userGameInfo->id);
//                if ($recharge) {
                    $userGame->last_recharge_type = 1;
//                }
                if (!$userGameInfo->pg_create) {
                    $userGame->pg_create = 1;
                }
                $userGame->pg_ops = $ops;
                $userGame->save();
            } else {
                $userGame = UserGame::find($userGameInfo->id);
                $userGame->pg_ops = $ops;
                $userGame->last_recharge_type = 1;
                $userGame->save();
            }

            DB::commit();

            $extraArgs = [
                'btt' => 1,
                'ops' => $ops,
                'l' => 'zh-cn',
                'f' => $parameter['referer'],
            ];
            $data = $this->http('/external-game-launcher/api/v1/GetLaunchURLHTML', [
                'player_name' => 'player1',
                'path' => '/'.$parameter['gameId'].'/index.html',
                'extra_args' => http_build_query($extraArgs),
                'url_type' => 'game-entry',
                'client_ip' => $this->getIp(),
            ], false);
            RedisLock::unLock($lockKey);
            return $this->apiSuccess('成功', ['data' => $data]);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('Pg_Transfer_Out')->info('LOGIN: ' . $exception->getMessage());
            RedisLock::unLock($lockKey);
            $this->apiError('系统错误，请联系客服。');
        }
    }

    /**
     * 获取PG钱包余额
     * @param $name
     * @return array|mixed|string|null
     * @throws ApiException
     */
    public function getAmount($name)
    {
        return $this->http('/external/Cash/v3/GetPlayerWallet', [
            'player_name' => $name
        ]);
    }

    /**
     * 转入PG
     * @param string $name
     * @param float $amount
     * @return void
     * @throws ApiException
     */
    protected function transferIn(string $name, float $amount)
    {
        $this->http('/external/Cash/v3/TransferIn', [
            'player_name' => $name,
            'amount' => $amount,
            'transfer_reference' => $this->RandCreateOrderNumber(),
            'currency' => 'CNY',
        ]);
    }

    /**
     * PG钱包余额转出
     * @param $name
     * @param $increment
     * @return void
     */
    public function transferOut($name, $increment = true)
    {
        try {
            DB::beginTransaction();
            $getAmount = $this->getAmount($name);
            $amount = floatval(intval($getAmount['data']['cashBalance']));
            if ($amount > 0) {
                $this->http('/external/Cash/v3/TransferOut', [
                    'player_name' => $name,
                    'amount' => $amount,
                    'transfer_reference' => $this->RandCreateOrderNumber(),
                    'currency' => 'CNY',
                ]);
                if ($increment) {
                    User::query()->where('id', request()->userinfo->id)->increment('account_balance', $amount);
                    (new ActivityService())->modifyAccount(request()->userinfo->id, 23, $amount);
                }
            }
            UserGame::query()->where('user_id', request()->userinfo->id)->update(['last_recharge_type' => 0]);
            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::channel('Pg_Transfer_Out')->info($name . ' -> ' . $exception->getMessage());
        }
    }

    /**
     * PG用户创建
     * @param string $name
     * @param string $nickname
     * @return void
     * @throws ApiException
     */
    protected function createUser(string $name, string $nickname)
    {
        $this->http('/external/v3/Player/Create', [
            'player_name' => $name,
            'nickname' => $nickname,
            'currency' => 'CNY',
        ]);
    }

}
