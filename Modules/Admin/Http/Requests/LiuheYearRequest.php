<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Requests\SceneValidator;

class LiuheYearRequest extends FormRequest
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
            'year'                      => 'required|unique:liuhe_years,year,'.request()->get('id'),
        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
            'year.required'                     => '请输入年份',
            'year.unique'                       => '年份已存在',
		];
	}

    public function scene()
    {
        return [
            'create'    => [
                'year'
            ]
        ];
    }
}









