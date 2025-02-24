<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class WebsiteRequest extends FormRequest
{
    use SceneValidator;

    private $arr;
    private $t;

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
        $this->arr = [
            'id'                => 'required|is_positive_integer',
            'web_name'          => 'required',
            'status'            => 'required', Rule::in([1, 2]),
            'web_url'           => 'required|url',
            'web_sign'          => 'required|unique:web_configs,web_sign,'.$this->get('id'),
            'avatar_prefix_url' => 'required',

        ];
        return $this->arr;
    }
	public function messages(){
        $this->t = [
            'id.required'                => '参数错误，请重试',
            'id.is_positive_integer'     => '参数错误，请重试',
            'web_name.required'          => '请输入网站名称',
            'web_sign.required'          => '网站标识必选',
            'web_sign.unique'            => '网站标识已存在',
            'status.required'            => '状态必选',
            'web_url.required'           => '网址必填',
            'web_url.url'                => '网址格式不对',
            'avatar_prefix_url.required' => '图片前缀必填',
        ];
        return $this->t;
	}

    public function scene()
    {
        return [
            //add 场景
            'create' => [
                'web_name',
                'web_sign',
                'web_url',
                'avatar_prefix_url',
                'status'
            ],
            'update' => [
                'id' ,
                'web_name',
                'web_sign',
                'web_url',
                'avatar_prefix_url',
                'status'
            ]

        ];
    }
}









