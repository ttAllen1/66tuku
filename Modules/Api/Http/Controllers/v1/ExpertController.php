<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\ExpertRequest;
use Modules\Api\Services\diagram\DiagramService;
use Modules\Api\Services\expert\ExpertService;
use Modules\Common\Exceptions\CustomException;

class ExpertController extends BaseApiController
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 列表
     * @param ExpertRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(ExpertRequest $request): JsonResponse
    {
        $request->validate('list');

        return (new ExpertService())->list($request->only(['lotteryType']));
        if ($request->input('type') == 1) {
            return (new ExpertService())->list($request->only(['lotteryType', 'sort', 'keyword']));
        } else {
            return (new DiagramService())->discuss_list($request->only(['lotteryType', 'sort', 'keyword']));
        }
    }

    /**
     * 发布
     * @param ExpertRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(ExpertRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new ExpertService())->create($request->only(['lotteryType', 'title', 'content', 'join', 'game_info', 'word_color', 'images']));
    }

    /**
     * 详情
     * @param ExpertRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail(ExpertRequest $request): JsonResponse
    {
        $request->validate('detail');

        return (new ExpertService())->detail($request->only(['id']));
    }

    /**
     * 上一期
     * @param ExpertRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function previous(ExpertRequest $request): JsonResponse
    {
        $request->validate('previous');

        return (new ExpertService())->previous($request->only(['lotteryType']));
    }

    /**
     * 点赞（全部主题）
     * @param ExpertRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(ExpertRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new ExpertService())->follow($request->only(['id']));
    }
}
