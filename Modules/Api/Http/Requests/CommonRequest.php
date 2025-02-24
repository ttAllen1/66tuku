<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class CommonRequest extends FormRequest
{
    use SceneValidator;

    public function autoValidate(){
        return false;  //关闭
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file.*'                => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'video.*'               => 'required|mimes:mp4,avi',
            'file_name'             => 'required',
            'upload_id'             => 'required',
            'parts'                 => 'required|array',
            'type'                  => 'required|'.Rule::in([1, 2]),
            'version'               => 'required|regex:/^\d+(\.\d+\.\d+)?$/',
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'file.required'                     => '图片不能为空！',
            'file.image'                        => '文件不是图片！',
            'file.mimes'                        => '图片仅支持jpeg,png,jpg,gif,webp！',
            'file.max'                          => '图片超过2M！',
            'video.required'                    => '视频不能为空！',
            'video.mimes'                       => '文件不是视频！',
            'file_name.required'                => '文件名必传！',
            'upload_id.required'                => 'uploadId必传！',
            'parts.required'                    => '分段信息必传！',
            'parts.array'                       => '分段信息格式不正确！',
            'type.required'                     => '类型必传',
            'type.Rule'                         => '类型格式不对',
            'version.required'                  => '版本必传',
            'version.regex'                     => '版本格式不对'
        ];
    }

    public function scene()
    {
        return [
            'image'             => ['file'],
            'video'             => ['video'],
            'minio_temp_cred'   => ['file_name'],
            'version'           => ['type', 'version'],
            'video_complete'    => ['file_name', 'upload_id', 'parts'],
        ];
    }
}
