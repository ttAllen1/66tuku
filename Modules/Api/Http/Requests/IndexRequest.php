<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'title.required' => '请输入标题！',
            'title.max' => '标题长度不能超过255位字符！',
            'title.min' => '标题长度不能小于2位字符！',
            'content.required' => '请输入内容！',
            'content.max' => '内容长度不能超过255位字符！',
            'content.min' => '内容长度不能小于2位字符！',
        ];
    }

}
