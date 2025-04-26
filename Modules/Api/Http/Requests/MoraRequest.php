<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class MoraRequest extends FormRequest
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
            'id'          => "required|is_positive_integer",
            'type'        => "nullable|sometimes|required|" . Rule::in([0, 1, 2]),
            'jia_content' => "required|" . Rule::in(["石头", "剪刀", "布"]),
            'money'       => "required|numeric|min:0.01",
            'page'        => 'required|is_positive_integer',
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
            'id.required'            => 'ID不能为空！',
            'id.is_positive_integer' => 'ID格式应为整数！',

            'jia_content.required' => '猜拳不能为空！',
            'jia_content.in'       => '只能选择石头、剪刀、布！',

            'money.required' => '金额不能为空！',
            'money.numeric'  => '金额格式应为数字！',
            'money.min'      => '金额不能小于0.01！',

            'page.required'            => '页码不能为空！',
            'page.is_positive_integer' => '页码必须为整数！',

        ];
    }

    public function scene()
    {
        return [
            'square' => ['page'],
            'list'   => ['page', 'type'],
            'create' => ['money', 'jia_content'],
            'join'   => ['id'],
        ];
    }
}
