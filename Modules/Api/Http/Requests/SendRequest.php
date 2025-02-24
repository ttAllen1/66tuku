<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Api\Services\common\CaptchaVerifierService;
use Modules\Common\Requests\SceneValidator;

class SendRequest extends FormRequest
{
    use SceneValidator;

    /**
     * php artisan module:make-request AdminRequest Admin
     */

    public function authorize()
    {
        return true;
    }

    /**
     * 设置是否自动验证
     * @return bool
     */
    public function autoValidate()
    {
        return false;  //关闭
    }

    public function rules()
    {
        return [
            'id'       => 'required|is_positive_integer',
            'mobile'   => 'required|regex:/^1[3456789]\d{9}$/',
            'sms_code' => 'required|string|size:6',
            'key'      => 'nullable',
            'scene'    => 'required|' . Rule::in(['register', 'login', 'bind', 'withdraw']),
            'captcha'  => 'nullable|captcha_api:' . request()->input('key') . ',four',
            'NECaptchaValidate' => ['nullable', function($attribute, $value, $fail){
                $result = (new CaptchaVerifierService())->verify($value);
                if (!$result) {
                    return $fail('验证失败');
                }
                return true;
            }],
//            'device'   => 'required|' . Rule::in(['ios', 'h5', 'android']),
        ];
    }

    public function messages()
    {
        return [
            'id.required'            => '参数错误，请重试',
            'id.is_positive_integer' => '参数错误，请重试',
            'mobile.required'        => '手机号必填',
            'mobile.regex'           => '手机号格式不允许',
            'sms_code.required'      => '短信验证码必填！',
            'sms_code.string'        => '短信验证码应为6位数字字符串！',
            'sms_code.size'          => '短信验证码应为6位数字字符串！',
            'scene.required'         => '场景必填',
            'scene.in'               => '场景值不允许',
            'key.required'           => '请点击图片重新获取验证码！',
            'captcha.required'       => '图形验证码必填',
            'captcha.captcha_api'    => '图形验证码校验失败',
//            'device.required'        => 'device必填',
//            'device.in'              => 'device值不允许',
        ];
    }

    public function scene()
    {
        return [
            //add 场景
            'mobile'        => ['mobile', 'scene', 'key', 'captcha', 'NECaptchaValidate'],
            'graph_verify'  => ['key', 'captcha'],
            'mobile_verify' => ['mobile', 'scene', 'sms_code'],
            'mobile_exist'  => ['mobile'],
            'mobile_login'  => ['mobile', 'scene', 'sms_code'],
        ];
    }
}









