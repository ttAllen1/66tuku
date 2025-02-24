<?php
/**
 * 游戏控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\game\GameService;
use Modules\Common\Controllers\BaseController;

class GameController extends BaseController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     * @description
     * @method  GET
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new GameService())->index($request->all());
    }

    public function store(Request $request): JsonResponse
    {
        return (new GameService())->store($request->except(['id']));
    }

    public function update(Request $request): ?JsonResponse
    {
        return (new GameService())->update($request->input('id'), $request->except(['id']));
    }

    public function update_status(Request $request): ?JsonResponse
    {
        return (new GameService())->update_status($request->input('id'), $request->except(['id']));
    }

    /**
     * 游戏配置列表
     * @return JsonResponse
     */
    public function game_config_index(): JsonResponse
    {
        return (new GameService())->game_config_index();
    }

    /**
     * 游戏配置列表
     * @param Request $request
     * @return JsonResponse
     */
    public function game_config_store(Request $request): JsonResponse
    {
        return (new GameService())->game_config_store($request->all());
    }

    /**
     * 游戏配置更新
     * @param Request $request
     * @return JsonResponse
     */
    public function game_config_update(Request $request): JsonResponse
    {
        return (new GameService())->game_config_update($request->all());
    }

    /**
     * 游戏配置删除
     * @param Request $request
     * @return JsonResponse
     */
    public function game_config_delete(Request $request): JsonResponse
    {
        return (new GameService())->game_config_delete($request->all());
    }
}
