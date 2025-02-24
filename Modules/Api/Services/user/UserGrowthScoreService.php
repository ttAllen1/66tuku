<?php

namespace Modules\Api\Services\user;

use Modules\Api\Models\User;
use Modules\Api\Models\UserGrowthScore;
use Modules\Api\Services\BaseApiService;

class UserGrowthScoreService extends BaseApiService
{
    /**
     * 增加成长值
     * @param $params
     * @return int|true
     */
    public function growthScore($params)
    {
        $userId = auth('user')->id();
        $date = date('Y-m-d');

        if (!$this->hasGrowth($userId, $date, $params)) {
            return true;
        }
        UserGrowthScore::query()->create([
            'user_id'   => $userId,
            'type'      => $params['type'],
            'score'     => $params['score'] ?? 1,
            'date'      => $date,
        ]);

        return User::query()->where('id', $userId)->increment('growth_score', $params['score'] ?? 1);
    }

    /**
     * 判断当天是否还能继续增加成长值
     * @param $userId
     * @param $date
     * @param  $params
     * @return bool
     */
    private function hasGrowth($userId, $date, $params): bool
    {
        $counts = UserGrowthScore::query()
            ->where('date', $date)
            ->where('type', $params['type'])
            ->where('user_id', $userId)
            ->count('*');

        return $counts<=$params['max_times'];
    }
}
