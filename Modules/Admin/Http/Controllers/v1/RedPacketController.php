<?php
/**
 * 聊天红包
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Services\chat\RedPacketService;
use Modules\Common\Controllers\BaseController;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class RedPacketController extends BaseController
{
    /**
     * 红包列表
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        return (new RedPacketService())->list($request->all());
    }

    /**
     * 红包随机金额
     *
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function round_num(Request $request): JsonResponse
    {
        return (new RedPacketService())->round_num($request->all());
    }

    /**
     * 发放红包
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function store(Request $request): JsonResponse
    {
        return (new RedPacketService())->store($request->all());
    }

    /**
     * 更新红包
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function update(Request $request): JsonResponse
    {
        return (new RedPacketService())->update($request->all());
    }
}
