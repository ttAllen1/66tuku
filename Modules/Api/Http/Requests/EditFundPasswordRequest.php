<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class EditFundPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password_current' => ['required', 'max:16', 'min:6', function($attribute, $value, $fail){
                if (!Hash::check(env('FUND_PASSWORD_SALT') . $value, auth('user')->user()->fund_password))
                {
                    return $fail('旧密码错误');
                }
            }],
            'password' => ['required', 'max:16', 'min:6', 'confirmed'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'password_current.required' => '请输入原密码！',
            'password_current.max' => '原密码长度不能超过16位字符！',
            'password_current.min' => '原密码长度不能小于6位字符！',
            'password.required' => '请输入密码！',
            'password.max' => '密码长度不能超过16位字符！',
            'password.min' => '密码长度不能小于6位字符！',
            'password.confirmed' => '两次密码不一致！',
        ];
    }

}
