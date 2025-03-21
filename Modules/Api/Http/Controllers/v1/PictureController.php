<?php
namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\PictureRequest;
use Modules\Api\Services\picture\PictureService;
use Modules\Common\Exceptions\CustomException;

class PictureController extends BaseApiController
{
    /**
     * 首页图片接口
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function index(PictureRequest $request)
    {
        $request->validate('index');

        return (new PictureService())->get_page_list($request->all());
    }

    /**
     * 首页图片接口[无广告]
     * @param PictureRequest $request
     * @return array|JsonResponse
     */
    public function pictures(PictureRequest $request)
    {
        $request->validate('index');

        return (new PictureService())->get_index_pic($request->all(), false, true, $request->input('pic_name', ''));
    }

    /**
     * 图库分类接口
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function cates(PictureRequest $request): JsonResponse
    {
        $request->validate('cate');

        return (new PictureService())->get_page_cate($request->all());
    }

    /**
     * 图片详情
     * pictureTypeId: 图片类型id 必传
     * pictureId: 图片id 不传：根据pictureTypeId查找显示最大一期数据
     *                  传：根据pictureId查找此照片信息
     * @param PictureRequest $request
     * @return null
     * @throws CustomException
     */
    public function detail(PictureRequest $request)
    {
        $request->validate('detail');

        return (new PictureService())->get_page_detail($request->all());
    }

    /**
     * 图片详情【投注站】
     * pictureTypeId: 图片类型id 必传
     * pictureId: 图片id 不传：根据pictureTypeId查找显示最大一期数据
     *                  传：根据pictureId查找此照片信息
     * @param PictureRequest $request
     * @return null
     * @throws CustomException
     */
    public function details(PictureRequest $request)
    {
        $request->validate('details');

        return (new PictureService())->get_page_detail_bet($request->all());
    }

    /**
     * 期数列表
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function issues(PictureRequest $request): JsonResponse
    {
        $request->validate('issues');

        return (new PictureService())->issues($request->only(['pictureTypeId', 'year']));
    }

    /**
     * 图片投票
     * @param PictureRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function vote(PictureRequest $request): JsonResponse
    {
        $request->validate('vote');

        return (new PictureService())->votes($request->all());
    }

    /**
     * 图片（取消）点赞
     * @param PictureRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function follow(PictureRequest $request): JsonResponse
    {
        $request->validate('follow');

        return (new PictureService())->follow($request->all());
    }

    /**
     * 图片（取消）收藏
     * @param PictureRequest $request
     * @return JsonResponse
     * @throws CustomException
     */
    public function collect(PictureRequest $request): JsonResponse
    {
        $request->validate('collect');

        return (new PictureService())->collect($request->all());
    }

    /**
     * 推荐图片
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function recommend(PictureRequest $request): JsonResponse
    {
        $request->validate('recommend');

        return (new PictureService())->recommends($request->input('year'), $request->input('pictureTypeId'), $request->input('lotteryType'));
    }

    /**
     * 图片系列
     * @return JsonResponse
     */
    public function series_list(): JsonResponse
    {
        return (new PictureService())->series_list();
    }

    /**
     * 系列详情
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function series_detail(PictureRequest $request): JsonResponse
    {
        $request->validate('series_detail');

        return (new PictureService())->series_detail($request->only(['id', 'keyword', 'lotteryType']));
    }

    public function video(PictureRequest $request)
    {
        $request->validate('video');
        return (new PictureService())->get_video_list($request->all());
    }

    /**
     * 图片AI分析
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function ai_analyze(PictureRequest $request): JsonResponse
    {
        $request->validate('ai_analyze');
        return (new PictureService())->ai_analyze($request->all());
    }

    /**
     * 图片流水
     * @param PictureRequest $request
     * @return JsonResponse
     */
    public function flow(PictureRequest $request): JsonResponse
    {
        $request->validate('index');

        return (new PictureService())->get_flow_list($request->all());
    }
}
