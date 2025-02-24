<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'content' => ['required', 'max:10', 'min:4'],
            'images' => ['required', 'array'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'content.required' => '请输入理由！',
            'content.max' => '理由最大255个字符！',
            'content.min' => '理由最小5个字符！',
            'images.required' => '请提交图片！',
            'images.array' => '请提交图片！',
        ];
    }

}
