<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class AdRequest extends FormRequest
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
            'id'                        => 'required',
            'title'                     => 'required',
            'type'                      => 'required',Rule::in([1,2]),
            'position'                  => 'required',Rule::in([1,2,3,4,5]),
            'ad_url'                    => 'required',
//            'is_forbid_withdrawal'      => 'required|is_positive_integer',

        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'title.required'                    => '请输入广告标题',
            'type.required'                     => '类型必选',
            'position.required'                 => '位置必选',
            'ad_url.required'                   => '链接必填',
		];
	}

    public function scene()
    {
        return [
            //add 场景
            'create' => [
                'title',
                'type',
                'position',
                'ad_url'
            ],
            'update' => [
                'id' ,       //复用 rules() 下 name 规则
                'title',
                'type',
                'position',
                'ad_url'
            ]

        ];
    }
}









