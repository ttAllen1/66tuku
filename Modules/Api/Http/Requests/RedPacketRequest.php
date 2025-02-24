<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Common\Requests\SceneValidator;

class RedPacketRequest extends FormRequest
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
            'id'         => 'required|is_positive_integer',
            'message_id' => 'required|is_positive_integer',
            'page'       => 'required|is_positive_integer',
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
            'id.required'                    => 'ID必填！',
            'id.is_positive_integer'         => 'ID格式不正确！',
            'message_id.required'            => '聊天室ID必填！',
            'message_id.is_positive_integer' => '聊天室ID格式不正确！',
            'page.required'                  => '页码必填！',
            'page.is_positive_integer'       => '页码格式不对！',
        ];
    }

    public function scene()
    {
        return [
            'receive'  => ['id'],
            'receives' => ['page'],
        ];
    }

}
