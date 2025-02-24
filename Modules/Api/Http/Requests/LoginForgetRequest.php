<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginForgetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'key' => 'required',
            'captcha' => 'required|captcha_api:' . request()->input('key').',four',
//            'phone' => ['required', new Phone, 'exists:auth_users'],
            'account_name' => ['required', 'max:10', 'min:5', 'regex:/^\w+$/', 'exists:users'],
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
            'key.required' => '请点击图片重新获取验证码！',
            'captcha.required' => '请输入验证码！',
            'captcha.captcha_api' => '验证码无效！',
//            'phone.required' => '请输入手机号码！',
//            'phone.exists' => '手机号未注册！',
            'account_name.required' => '请输入用户名！',
            'account_name.max' => '用户名长度不能超过10位字符！',
            'account_name.min' => '用户名长度不能小于5位字符！',
            'account_name.regex' => '用户名只能字母加数字！',
            'account_name.exists' => '用户名未注册！',
            'password.required' => '请输入密码！',
            'password.max' => '密码长度不能超过16位字符！',
            'password.min' => '密码长度不能小于6位字符！',
            'password.confirmed' => '两次密码不一致！',
        ];
    }

}
