<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;
use Modules\Common\Requests\SceneValidator;

class UserMushinRequest extends FormRequest
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
        $route = Route::current();
        $uniqueRule = 'required|array|';
        if ($route->getName() == 'user_mushin_store') {
            $uniqueRule .= 'unique:user_mushins,user_id';
        }
        return [
            'id'                        => 'required|is_positive_integer',
            'user_id'                   => $uniqueRule,
            'mushin_id'                 => 'required|is_positive_integer',
            'mushin_days'               => 'required|is_positive_integer'
        ];
    }
	public function messages(){
		return [
//            'id.required'                       => '参数错误，请重试',
//            'id.is_positive_integer'            => '参数错误，请重试',
//            'user_id.required'                  => '请选择禁言用户',
//            'user_id.unique'                    => '该用户已被禁言',
//            'mushin_id.required'                => '请选择禁言类型',
//            'mushin_days.required'              => '请填写禁言天数',
		];
	}

    public function scene()
    {
        return [
            //add 场景
            'create' => ['user_id', 'mushin_id'],
            'update' => ['id', 'user_id', 'mushin_id']
        ];
    }
}









