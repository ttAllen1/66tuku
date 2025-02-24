<?php
/**
 * 评论控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\user\UserCommentService;
use Modules\Common\Controllers\BaseController;
use Modules\Common\Exceptions\ApiException;

class UserCommentController extends BaseController
{
    /**
     * 列表
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function index(CommonPageRequest $request): JsonResponse
    {
        return (new UserCommentService())->comment_list($request->all());
    }

    /**
     * 更新
     * @param Request $request
     * @return JsonResponse|null
     */
    public function update(Request $request): ?JsonResponse
    {
        return (new UserCommentService())->update($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type', 'time_str', 'pic_detail']));
    }

    /**
     * 删除
     * @param Request $request
     * @return JsonResponse|null
     */
    public function delete(Request $request): ?JsonResponse
    {
        return (new UserCommentService())->delete($request->input('id'));
    }
    /**
     * @name 用户评论列表数据【第三方】
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function index3(CommonPageRequest $request)
    {
        return (new UserCommentService())->comment_list3($request->all());
    }

    public function update3(Request $request)
    {
//        dd($request->all());
        return (new UserCommentService())->update3($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type', 'time_str', 'images']));
    }

    public function delete3(Request $request)
    {
        return (new UserCommentService())->delete3($request->input('id'));
    }

    public function status(Request $request): ?JsonResponse
    {
        return (new UserCommentService())->status($request->input('status'));
    }

    /**
     * 后台创建评论
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function store(Request $request): JsonResponse
    {
        return (new UserCommentService())->store($request->all());
    }

    /**
     * 后台回复评论
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    public function reply(Request $request): JsonResponse
    {
        return (new UserCommentService())->reply($request->all());
    }
}
