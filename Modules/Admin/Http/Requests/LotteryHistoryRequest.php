<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class LotteryHistoryRequest extends FormRequest
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
            'year'                      => [
                                                'required',
                                                Rule::unique('history_numbers')->where(function ($query1) {
                                                    $query1->where('lotteryType', request()->get('lotteryType'))->where('issue', request()->get('issue'));
                                                })->ignore(request()->get('id'))
                                           ],
            'lotteryType'               => 'required',Rule::in([1,2,3,4,5]),
            'issue'                     => 'required',
            'number'                    => 'required',
            'attr_sx'                   => 'required',
            'attr_wx'                   => 'required',
            'lotteryTime'               => 'required',
//            'is_forbid_withdrawal'      => 'required|is_positive_integer',

        ];
    }
	public function messages(){
		return [
            'id.required'                       => '参数错误，请重试',
            'id.is_positive_integer'            => '参数错误，请重试',
		];
	}

    public function scene()
    {
        return [
            'create'    => ['year', 'lotteryType', 'issue', 'number', 'attr_sx', 'attr_wx'],
            'real_open' => ['issue', 'lotteryType', 'lotteryTime']
        ];
    }
}









