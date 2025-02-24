<?php
/**
 * 游戏管理服务
 * @Description
 */

namespace Modules\Admin\Services\game;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Modules\Admin\Services\BaseApiService;
use Modules\Api\Models\AuthGameConfig;
use Modules\Api\Models\Game;

class GameService extends BaseApiService
{
    /**
     * @param array $data
     * @return JsonResponse
     */
    public function index(array $data): JsonResponse
    {
        $list = Game::query()
            ->when(!empty($data['name']), function ($query) use ($data) {
                $query->where('name', 'like',  '%'.$data['name'].'%');
            })
            ->when($data['type'] != -2, function ($query) use ($data) {
                $query->where('type', $data['type']);
            })
            ->when($data['status'] != -2, function ($query) use ($data) {
                $query->where('status', $data['status']);
            })
            ->paginate($data['limit'])->toArray();

        return $this->apiSuccess('',[
            'list'  =>$list['data'],
            'total' =>$list['total'],
        ]);
    }

    /**
     * @param $id
     * @param array $data
     * @return JsonResponse|null
     */
    public function update($id,array $data): ?JsonResponse
    {
        unset($data['imageUrl']);
        $data['icon'] = str_replace($this->getHttp(), '', $data['icon']);
//        if ($data['type']==1) { // PG
//            $data['KindID'] = '';
//        } else {
//            $data['GameId'] = '';
//        }

        return $this->commonUpdate(Game::query(),$id,$data);
    }

    /**
     * @param array $data
     * @return JsonResponse
     */
    public function store(array $data): JsonResponse
    {
        unset($data['imageUrl']);
        return $this->commonCreate(Game::query(), $data);
    }

    /**
     * @param $id
     * @param array $data
     * @return JsonResponse
     */
    public function update_status($id, array $data): JsonResponse
    {
        return $this->commonUpdate(Game::query(), $id, $data);
    }

    /**
     * 游戏配置列表
     * @return JsonResponse
     */
    public function game_config_index(): JsonResponse
    {
        $list = AuthGameConfig::query()->orderBy('type')->get()->toArray();
        return $this->apiSuccess('', $list);
    }

    /**
     * 游戏配置创建
     * @param $data
     * @return JsonResponse
     */
    public function game_config_store($data): JsonResponse
    {
        AuthGameConfig::query()->updateOrCreate([
            'k' => $data['k']
        ], [
            'v' => $data['v'], 'description'=>$data['description'], 'type'=>$data['type']
        ]);
        $this->update_redis();

        return $this->apiSuccess();
    }

    /**
     * 游戏配置更新
     * @param $data
     * @return JsonResponse
     */
    public function game_config_update($data): JsonResponse
    {
        foreach ($data as $k => $v) {
            $data[$k] = json_decode($v, true);
        }
        AuthGameConfig::query()->delete();
        AuthGameConfig::query()->insert($data);
        $this->update_redis();

        return $this->apiSuccess();
    }

    /**
     * 游戏配置更新
     * @param $data
     * @return JsonResponse
     */
    public function game_config_delete($data): JsonResponse
    {
        AuthGameConfig::query()->where('k', $data['k'])->delete();
        $this->update_redis();

        return $this->apiSuccess();
    }

    public function update_redis()
    {
        $list = AuthGameConfig::query()->get();
        foreach ($list as $k => $item) {
            Redis::setex('auth_game_config_' . $item['k'], 3600, $item['v']);
        }

    }
}
