<?php
/**
 * @Name  图片上传服务
 * @Description
 */

namespace Modules\Admin\Services\upload;

use Illuminate\Http\Request;
use Modules\Admin\Models\AuthConfig as AuthConfigModel;
use Modules\Admin\Models\AuthImage as AuthImageModel;
use Modules\Admin\Services\BaseApiService;

class ImageService extends BaseApiService
{
    /**
     * @name  图片上传
     * @description
     * @param request Request 图片资源完整信息
     * @param request.file Resource  图片资源
     **/
    public function fileImage(object $request)
    {
        if ($request->isMethod('POST')){
            $location = $request->input('location', 'images');
            $fileCharater = $request->file('file');
            $mime = $fileCharater->getClientMimeType();
            if (str_contains($mime, 'video')) {
                $location = 'video';
            }
            $type = $request->input('type', 1);
            if ($fileCharater->isValid()){
                $imageStatus = AuthConfigModel::where('id',1)->value('image_status');
                if($imageStatus == 1){
                    $path = $request->file('file')->store($location.'/' . date('Ymd'),'upload');
                    if ($path){
                        $url = '/upload/'.$path;
                    }
                }else if($imageStatus == 2){
                    $url = $this->addQiniu($fileCharater);
                }
                if(isset($url)){
                    if ($type ==4) {
                        // 不插入auth_image表
                        return $this->apiSuccess('上传成功！',
                            [
                                'image_id'  => 0,
                                'type'      => $type,
                                'url'       => $location == 'video' ? (config('config.full_srv_img_prefix').'/upload/video_upload_ok.png') : config('config.full_srv_img_prefix').'/'.$url,
                                'video'     => $location == 'video' ? (config('config.full_srv_img_prefix').$url) : '',
                                'upload'    => $url
                            ]);
                    } else {
                        $image_id = AuthImageModel::insertGetId([
                            'url'=>$url,
                            'open'=>$imageStatus,
                            'status'=>1,
                            'created_at'=> date('Y-m-d H:i:s')
                        ]);
                        if($image_id){
                            return $this->apiSuccess('上传成功！',
                                [
                                    'image_id'  => $image_id,
                                    'type'      => $type,
                                    'url'       => config('config.full_srv_img_prefix').$url,
                                    'upload'    => $url
                                ]);
                        }
                    }


                }
            }
            $this->apiError('上传失败！');
        }
        $this->apiError('上传失败！');
    }
    /**
     * @name 七牛云图片上传
     * @description
     * @method  GET
     * @param fileCharater 图片对象
     * @return JSON
     **/
    private function addQiniu(object $fileCharater)
    {
        $this->apiError('七牛云存储暂未开放！');
        // 初始化
        $disk = QiniuStorage::disk('qiniu');
        // 重命名文件
        $fileName = md5($fileCharater->getClientOriginalName().time().rand()).'.'.$fileCharater->getClientOriginalExtension();
        // 上传到七牛
        $bool = $disk->put('iwanli/image_'.$fileName,file_get_contents($fileCharater->getRealPath()));
        // 判断是否上传成功
        if($bool){
            return $disk->downloadUrl('iwanli/image_'.$fileName);
        }else{
            return false;
        }
    }

    /**
     * @name 图片列表
     * @description
     * @param data Array 查询相关参数
     * @param data.page int 页码
     * @param data.limit int 每页显示条数
     * @return JSON
     **/
    public function getImageList(array $data){
        $model = AuthImageModel::query();
        $list = $model->select('id','open','url')->orderBy('id','desc')
            ->paginate($data['limit'])
            ->toArray();
        $http = $this->getHttp();
        foreach($list['data'] as $k=>$v){
            $list['data'][$k]['status'] = false;
            if($v['open'] == 1){
                $list['data'][$k]['url'] = $http . $v['url'];
            }else{
                $list['data'][$k]['url'] = $v['image_one']['url'];
            }
        }
        return $this->apiSuccess('',[
            'list'=>$list['data'],
            'total'=>$list['total']
        ]);
    }
}
