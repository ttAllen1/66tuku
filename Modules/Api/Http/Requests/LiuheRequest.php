<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class LiuheRequest extends FormRequest
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
            'id'                => 'required|is_positive_integer',
            'year'              => 'required|is_positive_integer',
            'page'              => 'required|is_positive_integer',
            'issue'             => 'required|is_positive_integer',
            'lotteryType'       => "required|".Rule::in([1, 2, 3, 4, 5, 6, 7]),
            'sort'              => "required|".Rule::in(['asc', 'desc']),
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'id.required'                           => 'ID不能为空！',
            'id.is_positive_integer'                => 'ID必须为整数！',
            'page.required'                         => '页码不能为空！',
            'page.is_positive_integer'              => '页码必须为整数！',
            'year.required'                         => '年份不能为空！',
            'year.is_positive_integer'              => '年份必须为整数！',
            'issue.required'                        => '期号不能为空！',
            'issue.is_positive_integer'             => '期号必须为整数！',
            'lotteryType.required'                  => '彩票分类不能为空！',
            'lotteryType.in'                        => '彩票ID不存在！',
            'sort.required'                         => '排序不能为空！',
            'sort.in'                               => '排序在\'asc\'和\'desc\'之间！',
        ];
    }

    public function scene()
    {
        return [
            'numbers'     => ['year', 'lotteryType'],
            'history'     => ['year', 'sort', 'lotteryType', 'page'],
            'recommend'   => ['year', 'lotteryType'],
            'record'      => ['id'],
            'statistics'  => ['lotteryType', 'issue'],
            'open_date'   => ['lotteryType'],
        ];
    }

}
