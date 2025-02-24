<?php

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\UserMobileBlackListService;
use Modules\Admin\Services\user\UserService;
use Modules\Admin\Services\user\UserWelfareService;
use Modules\Common\Controllers\BaseController;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class UserBlackListController extends BaseController
{
    /**
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new UserMobileBlackListService())->index($request->all());
    }

    /**
     * @param Request $request
     * @return JsonResponse|null
     */
    public function delete(Request $request): ?JsonResponse
    {
        return (new UserMobileBlackListService())->delete($request->input('id'));
    }

}
