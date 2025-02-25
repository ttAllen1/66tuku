<?php
/**
 * 六合配置
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\LiuheConfigRequest;
use Modules\Admin\Services\liuhe\LiuHeConfigService;

class LiuheConfigController extends BaseApiController
{
    /**
     * 列表数据
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new LiuHeConfigService())->index($request->all());
    }

    public function update(LiuheConfigRequest $request)
    {
        $request->validate();
        return (new LiuHeConfigService())->update($request->input('id', 0), $request->all());
    }

    public function store(LiuheConfigRequest $request)
    {
        $request->validate('create');
        return (new LiuHeConfigService())->store($request->except(['id']));
    }

    public function liuhe_lottery_index(CommonPageRequest $request)
    {
        return (new LiuHeConfigService())->liuhe_lottery_index($request->all());
    }

    public function liuhe_lottery_delete(Request $request)
    {
        return (new LiuHeConfigService())->liuhe_lottery_delete($request->input('id'));
    }

    public function liuhe_lottery_update(Request $request)
    {
        return (new LiuHeConfigService())->liuhe_lottery_update($request->input('id'), $request->except(['id']));
    }

    public function liuhe_lottery_store(LiuheConfigRequest $request)
    {
        $request->validate('liuhe_lottery_create');
        return (new LiuHeConfigService())->liuhe_lottery_create($request->only(['lotteryType', 'name', 'status', 'icon', 'icons']));
    }
}
