<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class PlatRequest extends FormRequest
{
    use SceneValidator;

    public function autoValidate()
    {
        return false;  //关闭
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'plat_id'           => "required|is_positive_integer",
            'plat_user_name'    => 'required',
            'plat_user_account' => 'required',
            'quota'             => 'required|is_positive_integer',
            'user_plat_id'      => 'required|is_positive_integer',
            'fund_password'     => 'bail|required|digits:6',
            'sms_code'          => 'required|string|size:6',
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
            'plat_id.required'                 => '平台Id不能为空！',
            'plat_id.is_positive_integer'      => '平台Id格式应为整数！',
            'plat_user_name.required'          => '平台姓名不能为空！',
            'plat_user_account.required'       => '平台账户不能为空',
            'quota.required'                   => '额度不能为空',
            'quota.is_positive_integer'        => '额度格式不正确',
            'user_plat_id.required'            => '用户平台ID不正确',
            'user_plat_id.is_positive_integer' => '用户平台ID格式不正确',
            'fund_password.required'           => '资金密码不能为空',
            'fund_password.digits'             => '资金密码格式不正确',
            'sms_code.required'                => '短信验证码必填！',
            'sms_code.string'                  => '短信验证码应为6位数字字符串！',
            'sms_code.size'                    => '短信验证码应为6位数字字符串！',
        ];
    }

    public function scene()
    {
        return [
            'bind'     => ['plat_id', 'plat_user_name', 'plat_user_account'],
            'recharge' => ['user_plat_id', 'quota'],
            'withdraw' => ['user_plat_id', 'quota', 'fund_password'],
        ];
    }
}
