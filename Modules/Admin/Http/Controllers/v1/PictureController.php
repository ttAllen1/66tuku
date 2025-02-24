<?php
/**
 * @Name 用户意见控制器
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Http\Requests\PictureRequest;
use Modules\Admin\Services\picture\PictureService;
use Modules\Common\Controllers\BaseController;
use Modules\Common\Exceptions\CustomException;

class PictureController extends BaseController
{
    /**
     * @name 图解列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function diagrams_list(CommonPageRequest $request)
    {

        return (new PictureService())->diagrams_list($request->all());
    }

    public function diagrams_update(Request $request)
    {
        return (new PictureService())->diagrams_update($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type']));
    }

    public function diagrams_delete(Request $request)
    {
        return (new PictureService())->diagrams_delete($request->input('id'));
    }
    /**
     * @name 竞猜列表数据
     * @description
     * @method  GET
     * @param  page Int 页码
     **/
    public function forecasts_list(CommonPageRequest $request)
    {
        return (new PictureService())->forecasts_list($request->all());
    }

    public function forecasts_update(Request $request)
    {
        return (new PictureService())->forecasts_update($request->input('id'), $request->except(['id', 'user', 'commentable_id', 'commentable_type']));
    }

    public function forecasts_delete(Request $request)
    {
        return (new PictureService())->forecasts_delete($request->input('id'));
    }

    /**
     * 首页图片列表
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        return (new PictureService())->list($request->all());
    }

    /**
     * 更改图片顺序
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        return (new PictureService())->update($request->input('id'), $request->all());
    }

    public function store(Request $request): JsonResponse
    {
        return (new PictureService())->store($request->all());
    }

    /**
     * 图片序列列表数据
     * @param CommonPageRequest $request
     * @return JsonResponse
     */
    public function series_list(CommonPageRequest $request): JsonResponse
    {
        return (new PictureService())->series_list($request->all());
    }

    /**
     * @param PictureRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function series_store(PictureRequest $request): JsonResponse
    {
        $request->validate('series_store');
        return (new PictureService())->series_store($request->all());
    }

    /**
     * @param PictureRequest $request
     * @return JsonResponse|null
     * @throws CustomException
     */
    public function series_update(PictureRequest $request): ?JsonResponse
    {
        $request->validate('series_update');
        return (new PictureService())->series_update($request->input('id'), $request->except(['id', 'index_pic_ids', 'index_pic_names', 'created_at', 'updated_at']));
    }

    public function series_delete(Request $request)
    {
        return (new PictureService())->series_delete($request->input('id'));
    }

    public function store_diagram(Request $request)
    {
        return (new PictureService())->store_diagram($request->all());
    }

    public function video_list(CommonPageRequest $request)
    {
        return (new PictureService())->video_list($request->all());
    }

    public function video_store(Request $request)
    {
        return (new PictureService())->video_store($request->all());
    }

    public function video_update(Request $request)
    {
        return (new PictureService())->video_update($request->input('id'), $request->only(['lotteryType', 'issue', 'pic_name']));
    }

    public function video_delete(Request $request)
    {
        return (new PictureService())->video_delete($request->input('id'));
    }

    public function update_is_video(Request $request)
    {
        return (new PictureService())->update_is_video($request->input('id'), $request->all());
    }

}
