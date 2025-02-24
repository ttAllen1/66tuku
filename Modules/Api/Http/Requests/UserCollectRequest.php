<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserCollectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => ['required', 'exists:user_collects'],
            'sorts' => ['nullable', 'regex:/[0-9]+$/u'],
            'remarks' => ['nullable', 'max:255', 'min:2'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'id.required' => '收藏不存在！',
            'id.exists' => '收藏不存在！',
            'sorts.regex' => '请输入数字',
            'remarks.max' => '备注最大255个字符',
            'remarks.min' => '备注最小2个字符',
        ];
    }

}
