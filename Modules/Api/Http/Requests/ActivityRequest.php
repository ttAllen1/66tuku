<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class ActivityRequest extends FormRequest
{
    use SceneValidator;

    public function autoValidate(){
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
            'invite_code'   => 'required|size:8|alpha_num',
            'type'          => 'required|'.Rule::in(['user_activity_forward', 'user_activity_follow', 'user_activity_comment', 'online_15', 'filling_invite_code', 'register_gift', 'share_gift', 'filling_gift']),
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'invite_code.required'  => '请输入邀请码！',
            'invite_code.size'      => '邀请码位数不正确！',
            'invite_code.alpha_num' => '邀请码格式不正确！',
            'type.required'         => '请输入类型！',
            'type.in'               => '类型不存在！',
        ];
    }

    public function scene()
    {
        return [
            'filling'        => ['invite_code'],
            'receive'        => ['type'],
        ];
    }

}
