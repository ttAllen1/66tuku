<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ThreeLoginRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'token'     => 'required',
            'user_id'   => 'required|is_positive_integer',
            'nick_name' => 'required',
            'avatar'    => 'required',
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'token.required'              => 'token必填！',
            'user_id.required'            => 'user_id必填',
            'user_id.is_positive_integer' => 'user_id格式必填',
            'nick_name.required'          => '昵称必填！',
            'avatar.required'             => '用户图像必填！',
        ];
    }

}
