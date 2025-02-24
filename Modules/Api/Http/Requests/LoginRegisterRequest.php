<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Api\Services\common\CaptchaVerifierService;

class LoginRegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mobile'       => 'nullable|unique:users,mobile',
            //            'key'               => 'nullable|required',
            'captcha'      => 'nullable|captcha_api:' . request()->input('key') . ',four',
            'account_name' => ['required', 'max:20', 'min:2', 'regex:/^\w+$/', 'unique:users'],
            'password'     => ['required', 'max:16', 'min:6', 'confirmed'],
            'invite_code'  => ['nullable', 'exists:users'],
            'sms_code'     => 'nullable|string|size:6',
            'NECaptchaValidate' => ['nullable', function($attribute, $value, $fail){
                $result = (new CaptchaVerifierService())->verify($value);
                if (!$result) {
                    return $fail('验证失败');
                }
                return true;
            }],
//            'device'       => 'required|' . Rule::in(['h5', 'ios', 'android']),
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
            'captcha.captcha_api'   => '验证码无效！',
            'account_name.required' => '请输入用户名！',
            'account_name.max'      => '用户名长度不能超过5位字符！',
            'account_name.min'      => '用户名长度不能小于2位字符！',
            'account_name.regex'    => '用户名只能字母加数字！',
            'account_name.unique'   => '用户名已经注册！',
            'password.confirmed'    => '两次密码不一致！',
            'invite_code.exists'    => '邀请码无效！',
            'sms_code.string'       => '短信验证码应为6位数字字符串！',
            'sms_code.size'         => '短信验证码应为6位数字字符串！',
            'mobile.unique'         => '手机号已存在',
//            'device.required'       => 'device必填',
//            'device.in'             => 'device值不允许',
        ];
    }

}
