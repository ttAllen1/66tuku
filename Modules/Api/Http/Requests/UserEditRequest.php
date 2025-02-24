<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserEditRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nickname' => ['nullable', 'max:30', 'min:2', 'regex:/[\w_\x{4e00}-\x{9fa5}]+$/u'],
            'avatar' => ['nullable', 'regex:/^upload[\w\/\.]+$/'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'nickname.max' => '昵称最大30个字符',
            'nickname.min' => '昵称最小2个字符',
            'nickname.regex' => '昵称只能字母数字汉字加下划线',
            'avatar.regex' => '请提交正确的头像地址',
        ];
    }

}
