<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class PictureRequest extends FormRequest
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
            'name'                      => 'required|unique:pic_series,name,'.$this->id,
            'status'                    => 'required|'.Rule::in([0, 1]),
            'sort'                      => 'is_positive_integer'
        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
            'name.required'                     => '名称必填',
            'name.unique'                       => '名称重复',
            'status.required'                   => '状态必填',
            'status.in'                         => '状态应为0或1',
            'sort.is_positive_integer'          => '排序应为整数',
		];
	}

    public function scene()
    {
        return [
            //add 场景
            'series_store'  => ['name', 'status', 'sort'],
            'series_update' => ['id', 'name', 'status', 'sort'],
        ];
    }
}









