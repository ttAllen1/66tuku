<?php

namespace Modules\Api\Services\three;

use Illuminate\Support\Facades\DB;
use Modules\Api\Services\BaseApiService;

class ThreeService extends BaseApiService
{
    protected $_secret = 'cea1ff3rf72^&HU7JdFwe232(*&^';
    public function queryIDByPlatAccount(array $data)
    {
        $platName = trim($data['plat_name']);
        $platUserName = trim($data['plat_user_name']);
        $platUserAccount = trim($data['plat_user_account']);

        if (md5($this->_secret.'#'.$platName.'#'.$platUserName.'#'.$platUserAccount) != $data['token']) {
            return false;
        }
        // 平台id
        $platId = DB::table('platforms')->where('name', $platName)->value('id');
        if (!$platId) {
            return null;
        }
        // 用户id
        $user_id = DB::table('user_platforms')
            ->where('plat_id', $platId)
            ->where('plat_user_name', $platUserName)
            ->where('plat_user_account', $platUserAccount)
            ->where('status', 1)
            ->value('user_id');
        if (!$user_id) {
            return null;
        }
        // 查询
        $res = DB::table('user_plat_recharge_dates')
            ->where('user_id', $user_id)
            ->where('plat_id', $platId)
            ->latest()
            ->select(['money', 'created_at'])
            ->first();
        if (!$res) {
            return null;
        }
        return $this->apiSuccess('', (array)$res);
//        dd($res, (array)$res);
    }
}
