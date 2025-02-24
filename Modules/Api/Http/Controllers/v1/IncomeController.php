<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Api\Http\Requests\IncomeRequest;
use Modules\Api\Services\income\IncomeService;
use Modules\Common\Exceptions\ApiException;

class IncomeController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 申请开通收益
     * @return JsonResponse
     */
    public function apple(): JsonResponse
    {

        return (new IncomeService())->apple();
    }

    /**
     * 打赏
     * @param IncomeRequest $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function reward(IncomeRequest $request): JsonResponse
    {
        $request->validate('reward');

        return (new IncomeService())->reward($request->all());
    }

    /**
     * 打赏列表
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function reward_list(CommonPageRequest $request): JsonResponse
    {
        return (new IncomeService())->reward_list($request->all());
    }

    public function posts_list(CommonPageRequest $request)
    {
        return (new IncomeService())->posts_list($request->all());
    }
}
