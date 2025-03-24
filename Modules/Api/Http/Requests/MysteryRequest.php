<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class MysteryRequest extends FormRequest
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
            'year'              => "required|is_positive_integer",
            'lotteryType'       => "required|".Rule::in([1, 2, 3, 4, 5, 6, 7]),
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'year.required'                     => '年份不能为空！',
            'year.is_positive_integer'          => '年份格式应为整数！',
            'lotteryType.required'              => '彩票分类不能为空！',
            'lotteryType.in'                    => '彩票ID不存在！',
        ];
    }

    public function scene()
    {
        return [
            'latest'        => ['year', 'lotteryType'],
            'history'       => ['year', 'lotteryType'],
        ];
    }

}
