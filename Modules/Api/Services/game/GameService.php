<?php

namespace Modules\Api\Services\game;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Modules\Api\Models\Game;
use Modules\Api\Services\BaseApiService;
use Modules\Api\Utils\RedisLock;
use Modules\Common\Exceptions\ApiException;

class GameService extends BaseApiService
{
    /**
     * 余额转出
     * @param $lastRechargeType
     * @param $name
     * @return void
     */
    public function transferOut($lastRechargeType, $name, $id)
    {
//        $repeatClick = Redis::get('other_game_transferOut_uid_' . $name);
//        if ($repeatClick) {
//            return;
//        }
//        Redis::setex('other_game_transferOut_uid_' . $name, 3, 1);

//        $lockKey = 'other_game_transferOut_lock_uid_' . $name;
        $lockKey = 'other_game_lock_uid_' . $id;
        if (RedisLock::lock($lockKey, 15)) {
            switch ($lastRechargeType) {
                case 1:
                    (new PgService())->transferOut($name);
                    break;

                case 2:
                    (new IMOneService())->transferOut($name);
                    break;

                case 3:
                    (new KyService())->transferOut($name);
                    break;

                case 4:
                    (new DagService())->transferOut($name);
                    break;

                case 5:
                    (new Pg2Service())->transferOut($name);
                    break;
            }
            RedisLock::unLock($lockKey);
        }
    }

    /**
     * 游戏列表
     * @param $parameter
     * @return JsonResponse|void
     * @throws ApiException
     */
    public function getList($parameter)
    {
        $gameList = Game::query()
            ->select(['id', 'name', 'GameId', 'icon', 'open_type'])
            ->where('type', $parameter['type'])
            ->where('status', 1)->get();
        if ($gameList) {
            return $this->apiSuccess('成功', $gameList->toArray());
        } else {
            $this->apiError('列表空');
        }
    }
}
