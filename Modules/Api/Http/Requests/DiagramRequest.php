<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class DiagramRequest extends FormRequest
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
            'page'                      => 'required|is_positive_integer',
            'id'                        => 'required|is_positive_integer',  // 图片详情表主键id
            'diagrams_id'               => 'required|is_positive_integer',  // 图片图解表主键id
            'lotteryType'               => "required|".Rule::in([1, 2, 3, 4, 5, 6, 7]),
            'year'                      => "required|is_positive_integer",
            'issue'                     => "required|is_positive_integer",
            'pictureId'                 => "required|is_positive_integer",
            'title'                     => ['required', 'max:255', 'min:2'],
            'content'                   => ['required', 'max:255', 'min:2'],
        ];
    }
    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages(){
        return [
            'id.required'                       => 'ID不能为空！',   // 图片详情id
            'id.is_positive_integer'            => 'ID必须为整数！',
            'diagrams_id.required'              => '图解ID不能为空！',
            'diagrams_id.is_positive_integer'   => '图解ID必须为整数！',
            'page.required'                     => '页码不能为空！',
            'page.is_positive_integer'          => '页码必须为整数！',
            'lotteryType.required'              => '彩票分类不能为空！',
            'lotteryType.in'                    => '彩票ID不存在！',
            'title.required'                    => '请输入标题！',
            'title.max'                         => '标题长度不能超过255位字符！',
            'title.min'                         => '标题长度不能小于2位字符！',
            'content.required'                  => '请输入内容！',
            'content.max'                       => '内容长度不能超过255位字符！',
            'content.min'                       => '内容长度不能小于2位字符！',
            'pictureId.required'                => '图片ID不能为空！',
            'pictureId.is_positive_integer'     => '图片ID应为整数！',
        ];
    }

    public function scene()
    {
        return [
            'create'        => ['id', 'title', 'content', 'issue', 'lotteryType'],
            'list'          => ['id', 'page'],
            'detail'        => ['id'],  // 图解id
            'follow'        => ['diagrams_id'],
        ];
    }

}
