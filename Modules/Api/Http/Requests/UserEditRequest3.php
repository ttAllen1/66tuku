<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserEditRequest3 extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nickname' => ['nullable'],
            'avatar' => ['nullable'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'nickname.max' => '昵称最大5个字符',
            'nickname.min' => '昵称最小2个字符',
            'nickname.regex' => '昵称只能字母数字汉字加下划线',
        ];
    }

}
