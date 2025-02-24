<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\CommentRequest;
use Modules\Api\Services\comment\CommentService;
use Modules\Common\Exceptions\CustomException;

class CommentController extends BaseApiController
{
    /**
     * 热门评论
     * @return JsonResponse
     */
    public function hot(): JsonResponse
    {
        return (new CommentService())->get_hot_list();
    }

    /**
     * 一级评论
     * @param CommentRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function list(CommentRequest $request): JsonResponse
    {
        $request->validate('comment');

        return (new CommentService())->list($request->all());
    }

    /**
     * 获取子评论
     * @param CommentRequest $request
     * @return JsonResponse
     */
    public function children(CommentRequest $request): JsonResponse
    {
        $request->validate('children');

        return (new CommentService())->get_children_list($request->all());
    }

    /**
     * 发布评论
     * @param CommentRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create(CommentRequest $request): JsonResponse
    {
        $request->validate('create');
        return (new CommentService())->create($request->all());
    }

    /**
     * 点赞评论
     * @param CommentRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(CommentRequest $request): JsonResponse
    {
        $request->validate('follow');
        return (new CommentService())->follow($request->all());
    }

    /**
     * 点赞评论｜图片【第三方】
     * @param CommentRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow_3(CommentRequest $request): JsonResponse
    {
        $request->validate('follow_3');

        return (new CommentService())->follow_3($request->only('cate','target_id'));
    }

    /**
     * 发布评论【第三方】
     * @param CommentRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function create_3(CommentRequest $request): JsonResponse
    {
        $request->validate('create_3');
        return (new CommentService())->create_3($request->only(['three_cate', 'target_id', 'content', 'user_id', 'nick_name', 'avatar']));
    }

    /**
     * 一级评论
     * @param CommentRequest $request
     * @return JsonResponse
     */
    public function list_3(CommentRequest $request): JsonResponse
    {
        $request->validate('list_3');

        return (new CommentService())->list_3($request->only(['page', 'three_cate','target_id']));
    }

    /**
     * 获取子评论
     * @param CommentRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function children_3(CommentRequest $request): JsonResponse
    {
        $request->validate('children_3');

        return (new CommentService())->children_3($request->only(['location', 'commentId', 'page']));
    }
}
