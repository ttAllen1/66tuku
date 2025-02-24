<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class IncomeRequest extends FormRequest
{
    use SceneValidator;

    public function autoValidate()
    {
        return false;  //关闭
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'target_id'      => 'required|is_positive_integer',
            'target_user_id' => 'required|is_positive_integer',
            'type'           => 'required|' . Rule::in(['1', '2', '3']),
            'reward_money'   => 'required|regex:/^\d+\.\d{2}$/',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'target_id.required'                 => 'id必填！',
            'target_id.is_positive_integer'      => 'id格式错误！',
            'target_user_id.required'            => '被打赏者必填！',
            'target_user_id.is_positive_integer' => '被打赏者必填！',
            'reward_money.required'              => '打赏金额必填！',
            'reward_money.regex'                 => '打赏金额必须为两位小数！',
            'type.required'                      => '请输入类型！',
            'type.in'                            => '类型不存在！',
        ];
    }

    public function scene()
    {
        return [
            'reward' => ['target_id', 'reward_money', 'type'],
        ];
    }

}
