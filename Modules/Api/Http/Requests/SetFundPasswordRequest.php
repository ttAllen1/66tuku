<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetFundPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password' => ['required', 'max:16', 'min:6', function($attribute, $value, $fail){
                if (auth('user')->user()->fund_password)
                {
                    return $fail('支付密码已设置');
                }
            }],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'password.required' => '请输入密码！',
            'password.max' => '密码长度不能超过16位字符！',
            'password.min' => '密码长度不能小于6位字符！',
        ];
    }

}
