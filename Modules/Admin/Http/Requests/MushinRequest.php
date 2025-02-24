<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class MushinRequest extends FormRequest
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
            'id'                       => 'required|is_positive_integer',
            'name'                     => 'required',
            'status'                   => 'required',Rule::in([1,2]),
            'mushin_period'            => 'required|is_positive_integer',

        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
            'name.required'                     => '请输入禁言名称',
            'mushin_period.is_positive_integer' => '禁言周期为正整数',
		];
	}

    public function scene()
    {
        return [
            //add 场景
            'create' => [
                'name',
                'status',
                'mushin_period'
            ]
        ];
    }
}









