<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\CorpusArticleRequest;
use Modules\Api\Http\Requests\LoginRequest;
use Modules\Api\Services\corpus\CorpusService;
use Modules\Common\Exceptions\CustomException;

class CorpusController extends BaseApiController
{
    /**
     * 获取资料分类
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function listCorpusType(Request $request): JsonResponse
    {
        return (new CorpusService())->listCorpusType($request->input());
    }

    /**
     * 资料列表
     * @param Request $request
     * @return JsonResponse
     */
    public function listArticle(Request $request): JsonResponse
    {
        return (new CorpusService())->listArticle($request->input());
    }

    /**
     * 资料详情
     * @param CorpusArticleRequest $request
     * @return JsonResponse
     */
    public function infoArticle(CorpusArticleRequest $request): JsonResponse
    {
        return (new CorpusService())->infoArticle($request->only(['id', 'corpusTypeId']));
    }

    /**
     * 点赞
     * @param Request $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(Request $request): JsonResponse
    {
        return (new CorpusService())->follow($request->only(['id', 'corpusTypeId']));
    }

}
