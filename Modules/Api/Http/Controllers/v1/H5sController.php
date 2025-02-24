<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Services\h5s\H5sService;

class H5sController extends BaseApiController
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 前端地址
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return (new H5sService())->index($request->all());
    }
}
