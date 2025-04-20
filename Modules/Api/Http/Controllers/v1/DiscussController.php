<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\DiscussRequest;
use Modules\Api\Services\diagram\DiagramService;
use Modules\Api\Services\discuss\DiscussService;
use Modules\Common\Exceptions\CustomException;

class DiscussController extends BaseApiController
{
    public function __construct(){
        parent::__construct();
    }

    /**
     * 列表
     * @param DiscussRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(DiscussRequest $request): JsonResponse
    {
        $request->validate('list');

        if ($request->input('type') == 1) {
            return (new DiscussService())->list($request->only(['lotteryType', 'sort', 'keyword']));
        } else {
            return (new DiagramService())->discuss_list($request->only(['lotteryType', 'sort', 'keyword']));
        }
    }

    /**
     * 发布
     * @param DiscussRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(DiscussRequest $request): JsonResponse
    {
        $request->validate('create');

        return (new DiscussService())->create($request->only(['lotteryType', 'title', 'content', 'year', 'word_color', 'images']));
    }

    public function three_create(DiscussRequest $request)
    {
        $request->validate('create');
        $params = $request->only(['lotteryType', 'title', 'content', 'year', 'word_color', 'images', 'user_id_49']);
        $params['is_49'] = 1;
        return (new DiscussService())->create($params);
    }


    /**
     * 详情
     * @param DiscussRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function detail(DiscussRequest $request): JsonResponse
    {
        $request->validate('detail');

        return (new DiscussService())->detail($request->only(['id']));
    }

    /**
     * 上一期
     * @param DiscussRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function previous(DiscussRequest $request): JsonResponse
    {
        $request->validate('previous');

        return (new DiscussService())->previous($request->only(['lotteryType']));
    }

    /**
     * 点赞（全部主题）
     * @param DiscussRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(DiscussRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new DiscussService())->follow($request->only(['id']));
    }
}
