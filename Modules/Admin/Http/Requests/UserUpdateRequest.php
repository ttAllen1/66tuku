<?php

namespace Modules\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class UserUpdateRequest extends FormRequest
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
            'id'                => 'required|is_positive_integer',
            'account_name'      => 'required',
            'name'              => 'required',
            'level_id'          => 'required|is_positive_integer',
            'status'            => 'required|' . Rule::in([1, 2]),
            //            'is_forbid_withdrawal'      => 'required|is_positive_integer',
            'is_forbid_bet'     => 'required',
            'is_balance_freeze' => 'required|' . Rule::in([1, 2]),
            'is_forbid_speak'   => 'required|' . Rule::in([1, 2]),
            'phone'             => 'required|regex:/^1[34578]{1}\d{9}$/|unique:auth_users,phone,' . $this->get('id'),
            'email'             => 'required|regex:/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/|unique:auth_users,email,' . $this->get('id'),
            'password'          => 'required|alpha_num|user_pw_between',
            'fund_password'     => 'required|alpha_num|user_pw_between',
            'room_id'           => 'required|is_positive_integer'
        ];
    }

    public function messages()
    {
        return [
            'id.required'                   => '参数错误，请重试',
            'id.is_positive_integer'        => '参数错误，请重试',
            'account_name.required'         => '请输入登录账号',
            'name.required'                 => '请输入姓名',
            'level_id.required'             => '请选择等级',
            'level_id.is_positive_integer'  => '等级ID不允许',
            'status.required'               => '请选择状态',
            'status.in'                     => '状态不允许',
            'is_forbid_withdrawal.required' => '请选择提现状态',
            'is_forbid_bet.required'        => '请选择投注状态',
            'is_balance_freeze.required'    => '请选择冻结状态',
            'is_balance_freeze.in'          => '冻结状态不允许',
            'is_forbid_speak.required'      => '请选择禁言状态',
            'is_forbid_speak.in'            => '禁言状态不允许',
            'password.required'             => '密码必填',
            'password.alpha_num'            => '密码只能是数字和字母',
            'password.user_pw_between'      => '密码只能是6到8位',
            'phone.required'                => '请输入手机号！',
            'phone.unique'                  => '手机号已注册！',
            'phone.regex'                   => '请输入正确的手机号！',
            'email.required'                => '请输入邮箱！',
            'email.unique'                  => '邮箱已注册！',
            'email.regex'                   => '请输入正确的邮箱！',
            'room_id.required'              => '房间号必填！',
            'room_id.is_positive_integer'   => '请输入正确的房间号！',

        ];
    }

    public function scene()
    {
        return [
            //add 场景
            'add'                 => [
                'name',       //复用 rules() 下 name 规则
                'email' => 'email|required|unique:users'  //重置规则
            ],
            'change_user_pw'      => [
                'id', 'password'
            ],
            'change_user_fund_pw' => [
                'id', 'fund_password'
            ],
            'change_user_info'    => [
                'id', 'account_name', 'level_id', 'status', 'is_forbid_withdrawal', 'is_forbid_bet',
                'is_balance_freeze', 'is_forbid_speak',
                'password' => 'alpha_num|user_pw_between',
            ],
            'add_user_info'       => [
                'account_name', 'level_id', 'status', 'is_forbid_withdrawal', 'is_forbid_bet',
                'is_balance_freeze', 'is_forbid_speak',
                'password' => 'alpha_num|user_pw_between',
            ],
            'user_mushin'         => [
                'account_name'
            ],
            'user_forbid_speak'   => [
                'id', 'room_id'
            ]

        ];
    }
}









