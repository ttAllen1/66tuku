<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class ExpertRequest extends FormRequest
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
            'id'                => "required|is_positive_integer",
            'title'             => 'required',
            'content'           => 'required',
            'word_color'        => 'required',
            'lotteryType'       => "required|".Rule::in([0, 1, 2, 3, 4, 5, 6, 7]),
            'type'              => "required|".Rule::in([1, 2]),
            'cate'              => "required|".Rule::in([1, 2]),
            'sort'              => "required|".Rule::in([1, 2, 3, 4]),
            'join'              => "required|".Rule::in([0, 1]),
            'game_info'              => "required_if:join,1|json",
            'year'              => 'required|is_positive_integer',
            'issue'             => 'required|is_positive_integer',
            'page'              => 'required|is_positive_integer',
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'id.required'                       => 'ID不能为空！',
            'id.is_positive_integer'            => 'ID格式应为整数！',
            'lotteryType.required'              => '类型不能为空！',
            'lotteryType.in'                    => '彩票ID不存在！',
            'type.required'                     => '分类不能为空！',
            'type.in'                           => '分类在1~2之间！',
            'cate.required'                     => '分类不能为空！',
            'cate.in'                           => '分类在1~2之间！',
            'sort.required'                     => '排序不能为空！',
            'sort.in'                           => '排序在1~4之间！',
            'title'                             => '标题必填',
            'content'                           => '内容必填',
            'word_color'                        => '字体颜色必填',
            'page.required'                     => '页码不能为空！',
            'page.is_positive_integer'          => '页码必须为整数！',
            'year.required'                     => '年份不能为空！',
            'year.is_positive_integer'          => '年份必须为整数！',
            'issue.required'                    => '期数不能为空！',
            'issue.is_positive_integer'         => '期数必须为整数！',
        ];
    }

    public function scene()
    {
        return [
            'list'          => ['lotteryType'],
            'create'        => ['lotteryType', 'title', 'content', 'word_color', 'join', 'game_info'],
            'detail'        => ['id'],
            'follow'        => ['id'],
            'previous'      => ['lotteryType'],
        ];
    }
}
