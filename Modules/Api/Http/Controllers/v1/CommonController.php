<?php

namespace Modules\Api\Http\Controllers\v1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Requests\CommonRequest;
use Modules\Api\Services\common\CommonService;
use Modules\Common\Exceptions\ApiException;
use Modules\Common\Exceptions\CustomException;

class CommonController extends BaseApiController
{
    /**
     * 公共配置
     * @return JsonResponse
     */
    public function config(): JsonResponse
    {
        return (new CommonService())->config();
    }

    /**
     * 获取最新版本信息
     * @param CommonRequest $request
     * @return null
     */
    public function version(CommonRequest $request)
    {
        $request->validate('version');

        return (new CommonService())->version($request->only(['type', 'version']));
    }

    /**
     * 公共上传
     * @param Request $request
     * @return JsonResponse|null
     */
    public function upload(Request $request): ?JsonResponse
    {
        return (new CommonService())->fileImage($request);
    }

    /**
     * 上传图片
     * @param CommonRequest $request
     * @return JsonResponse
     * @throws CustomException|ApiException
     */
    public function image(CommonRequest $request): JsonResponse
    {
        $request->validate('image');
        return (new CommonService())->imageUpload($request);
    }

    /**
     * 上传视频【本地】【没用到】
     * @param CommonRequest $request
     * @return null
     */
    public function video(CommonRequest $request)
    {
        $request->validate('video');

        return (new CommonService())->videoUpload($request);
    }

    /**
     * 公告 ｜ 通知
     * @param Request $request
     * @return JsonResponse
     */
    public function getMessage(Request $request): JsonResponse
    {
        return (new CommonService())->getMessage($request->input('type', 1));
    }

    /**
     * 通知已读
     * @return JsonResponse
     * @throws CustomException
     */
    public function setMessage(): JsonResponse
    {
        return (new CommonService())->setMessage();
    }

    /**
     * 通知数
     * @return JsonResponse
     */
    public function getMessageBadge(): JsonResponse
    {
        return (new CommonService())->getMessageBadge();
    }

    /**
     * 临时凭据
     * @param CommonRequest $request
     * @return JsonResponse
     */
    public function minio_temp_cred(CommonRequest $request): JsonResponse
    {
        $request->validate('minio_temp_cred');

        return (new CommonService())->tempCred($request->only(['file_name']));
    }

    /**
     * 合并视频
     * @param CommonRequest $request
     * @return JsonResponse
     */
    public function minio_video_complete(CommonRequest $request): JsonResponse
    {
        $request->validate('video_complete');

        return (new CommonService())->videoComplete($request->only(['file_name', 'upload_id', 'parts']));
    }

}
