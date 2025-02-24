<?php

namespace Modules\Api\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Api\Models\AuthActivityConfig;
use Modules\Api\Models\Platform;
use Modules\Api\Models\UserFivePlatRechargeDate;
use Modules\Api\Models\UserPlatform;
use Modules\Api\Services\BaseApiService;
use Modules\Common\Exceptions\CustomException;

class FindRecharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $uid;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $userPlatform = UserPlatform::query()->where(['user_id' => $this->uid, 'status' => 1])->get();
        if ($userPlatform) {
            $platformConf = [];
            $platform = Platform::select(['id', 'token', 'query_user_recharge_api', 'status'])->where('status', 1)->get();
            foreach ($platform as $platformItem) {
                $platformConf[$platformItem->id] = $platformItem->toArray();
            }
            $rechargeTotal = 0;
            foreach ($userPlatform as $userPlatfomItem) {
                if (isset($platformConf[$userPlatfomItem->plat_id])) {
                    $rechargeAmount = $this->query([
                        'token' => $platformConf[$userPlatfomItem->plat_id]['token'],
                        'query_user_recharge_api' => $platformConf[$userPlatfomItem->plat_id]['query_user_recharge_api'],
                        'plat_user_account' => $userPlatfomItem->plat_user_account,
                        'plat_id' => $userPlatfomItem->plat_id,
                    ]);
                    $rechargeTotal += $rechargeAmount;
                }
            }
            if ($rechargeTotal >= 10) {
                (new BaseApiService())->joinActivities(7, $this->uid);
            }
        }
    }

    private function query($parameter)
    {
        try{
            $token = $parameter['token'];
            $query_user_recharge_api = $parameter['query_user_recharge_api'];
            $plat_user_account = $parameter['plat_user_account'];
            $StartDate = AuthActivityConfig::val('five_bliss_start');
            $EndDate = AuthActivityConfig::val('five_bliss_end');
            $sign = strtolower(md5("UserRectotal#".$plat_user_account.'#'.$StartDate.'#'.$EndDate.'#'.$token));
            $response = Http::withOptions([
                'verify'=>false
            ])->timeout(5)->retry(3, 100)->get(sprintf($query_user_recharge_api, $plat_user_account, $sign, $StartDate, $EndDate));
            if ($response->status() != 200) {
                throw new CustomException(['message'=>'与彩票平台通信中断']);
            }
            $res = json_decode($response->body(), true);
            if ($res['Status'] == 0 && $res['RecTotal'] > 0) { // 查询成功有充值金额
//                (new BaseApiService())->joinActivities(7, $this->uid);
                UserFivePlatRechargeDate::updateOrInsert([
                    'user_id' => $this->uid,
                    'plat_id' => $parameter['plat_id']
                ], [
                    'money' => $res['RecTotal']
                ]);
                return $res['RecTotal'];
            }
        } catch (ConnectionException $exception) {
            throw new CustomException(['message'=>'与彩票平台通信超时']);
        } catch (RequestException $exception) {
            throw new CustomException(['message'=>'与彩票平台尝试通信失败']);
        } catch (\Exception $exception) {
            throw new CustomException(['message'=>'获取失败']);
        }
    }
}
