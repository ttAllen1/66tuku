<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Requests\SceneValidator;

class AnnounceRequest extends FormRequest
{
    use SceneValidator;
    /**
     * php artisan module:make-request AdminRequest Admin
     */

    public function authorize()
    {
        return true;
    }
    /**
     * 设置是否自动验证
     * @return bool
     */
    public function autoValidate(){
        return false;  //关闭
    }
	public function rules()
    {
        return [
            'id'                        => 'required|is_positive_integer',
            'user_id'                   => 'required|array',
            'reply_content'             => 'required'
        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
            'user_id.required'                  => '请选择禁言用户',
            'reply_content.required'            => '回复内容不能为空',
		];
	}

    public function scene()
    {
        return [
            //add 场景
            'create' => ['user_id'],
            'reply' => [
                'id', 'reply_content'
            ]
        ];
    }
}









