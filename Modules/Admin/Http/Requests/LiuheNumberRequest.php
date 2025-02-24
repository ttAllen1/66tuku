<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Requests\SceneValidator;

class LiuheNumberRequest extends FormRequest
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
            'year'                      => 'required',
            'number'                    => 'required',

        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
            'year.required'                     => '请输入年份',
            'number.required'                   => '请输入数字',
		];
	}

    public function scene()
    {
        return [
            'create'    => ['year']
        ];
    }
}









