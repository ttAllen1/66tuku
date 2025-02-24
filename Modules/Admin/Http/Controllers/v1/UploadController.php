<?php
/**
 * @Name 图片管理
 * @Description
 */

namespace Modules\Admin\Http\Controllers\v1;

use Illuminate\Http\Request;
use Modules\Admin\Http\Requests\CommonPageRequest;
use Modules\Admin\Services\upload\ImageService;

class UploadController extends BaseApiController
{
    /**
     * @name  图片上传
     * @description
     * @method  POST
     * @param request Request 图片资源完整信息
     * @param request.file Resource  图片资源
     **/
    public function fileImage(Request $request){
        return (new ImageService())->fileImage($request);
    }
    /**
     * @name 图片列表
     * @description
     * @method  GET
     * @param page int 页码
     * @param limit int 每页显示条数
     **/
    public function getImageList(CommonPageRequest $request){
        return (new ImageService())->getImageList($request->only(['page','limit']));
    }
}
