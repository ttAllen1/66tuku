<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\MoraRequest;
use Modules\Api\Services\mora\MoraService;
use Modules\Common\Exceptions\CustomException;

class MoraController extends BaseApiController
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 列表
     * @param MoraRequest $request
     * @return JsonResponse
     */
    public function list(MoraRequest $request): JsonResponse
    {
        $request->validate('list');

        return (new MoraService())->list($request->only(['type', 'page']));
    }

    /**
     * 猜拳广场
     * @param MoraRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function square(MoraRequest $request): JsonResponse
    {
        $request->validate('square');

        return (new MoraService())->square($request->only(['account_name', 'page']));
    }

    /**
     * 发布
     * @param MoraRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(MoraRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new MoraService())->create($request->only(['money', 'jia_content']));
    }

    /**
     * 参与
     * @param MoraRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function join(MoraRequest $request): JsonResponse
    {
        $request->validate('join');

        return (new MoraService())->join($request->only(['id', 'yi_content']));
    }

}
