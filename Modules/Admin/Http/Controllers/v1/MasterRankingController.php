<?php
/**
 * 高手榜控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\master\MasterRankingService;

class MasterRankingController extends BaseApiController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new MasterRankingService())->index($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function update(Request $request): ?JsonResponse
    {
        return (new MasterRankingService())->update($request->only(['id', 'content', 'praise_num', 'lotteryType']));
    }
}
