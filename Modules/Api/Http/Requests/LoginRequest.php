<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Modules\Api\Services\common\CaptchaVerifierService;

class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'key'          => 'nullable',
            'captcha'      => 'nullable|captcha_api:' . request()->input('key') . ',four',
            //            'phone' => ['required', new Phone],
            'account_name' => ['required', 'max:20', 'min:2', 'exists:users'],
            'password'     => 'required',
            'mobile'       => 'nullable|unique:users,mobile,'. $this->account_name .',account_name',
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
            'key.required'          => '请点击图片重新获取验证码！',
            'captcha.captcha_api'   => '验证码无效',
            'account_name.required' => '请输入用户名！',
            'account_name.max'      => '用户名长度不能超过20位字符！',
            'account_name.min'      => '用户名长度不能小于2位字符！',
            //            'account_name.regex' => '用户名只能字母加数字！',
            'account_name.exists'   => '用户名未注册！',
            //            'phone.required' => '请输入手机号码！',
            'password.required'     => '请输入密码！',
            'sms_code.string'       => '短信验证码应为6位数字字符串！',
            'sms_code.size'         => '短信验证码应为6位数字字符串！',
            'mobile.unique'         => '手机号已存在',
//            'device.required'       => 'device必填',
//            'device.in'             => 'device值不允许',
        ];
    }

}
