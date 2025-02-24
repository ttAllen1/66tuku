<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class OpenLotteryRequest extends FormRequest
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
            'lotteryType'       => "required|".Rule::in([1, 2, 3, 4]),
            'client_id'         => 'required',
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'lotteryType.required'              => '彩票分类不能为空！',
            'lotteryType.in'                    => '彩票分类在1~4之间！',
            'client_id.required'                => 'client_id必传！',
        ];
    }

    public function scene()
    {
        return [
            'open'        => ['client_id', 'lotteryType'],
        ];
    }

}
