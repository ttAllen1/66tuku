<?php
namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Services\cache\CacheService;

class CacheController extends BaseApiController
{
    /**
     * 缓存首页图片列表
     * @param Request $request
     * @return int
     */
    public function index_pic(Request $request): int
    {
        return (new CacheService())->index_pic();
    }
}
