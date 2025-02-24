<?php

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Common\Controllers\BaseController;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class UserWelfareController extends BaseController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new UserWelfareService())->index($request->all());
    }

    /**
     *  创建
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function store(Request $request): JsonResponse
    {
        return (new UserWelfareService())->store($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     * @throws CustomException
     */
    public function update(Request $request): ?JsonResponse
    {
        return (new UserWelfareService())->update($request->input('id'), $request->except(['user']));
    }

}
