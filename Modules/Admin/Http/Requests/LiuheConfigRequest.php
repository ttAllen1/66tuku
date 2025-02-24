<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Requests\SceneValidator;

class LiuheConfigRequest extends FormRequest
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
            'config_title'              => 'required',
            'config_code_mark'          => 'required|unique:liuhe_configs,config_code_mark,'.request()->get('id'),
            'status'                    => 'required|min:1|max:2',
        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
            'config_title.required'             => '配置标题不能为空',
            'config_code_mark.required'         => '代码标识不能为空',
            'config_code_mark.unique'           => '代码标识已存在',
            'status.required'                   => '请选择状态',
		];
	}

    public function scene()
    {
        return [
            'create'    => [
                'config_title', 'config_code_mark', 'status'
            ]
        ];
    }
}









