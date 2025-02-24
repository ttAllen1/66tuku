<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class DiscoveryRequest extends FormRequest
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
            'year'              => "required|is_positive_integer",
            'type'              => "required|".Rule::in([1, 2]),
            'title'             => 'required',
            'content'           => 'required',
            'lotteryType'       => "required|".Rule::in([1, 2, 3, 4, 5, 6, 7]),
            'page'              => 'required|is_positive_integer',
            'is_rec'            => "required|".Rule::in([0, 1, 2]),
            'images'            => "required_if:type,1",
            'videos'            => "required_if:type,2",
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
            'year.required'                     => '年份不能为空！',
            'year.is_positive_integer'          => '年份格式应为整数！',
            'lotteryType.required'              => '彩票分类不能为空！',
            'lotteryType.in'                    => '彩票分类在1~5之间！',
            'title.required'                    => '标题不能为空！',
            'content.required'                  => '内容不能为空！',
            'page.required'                     => '页码不能为空！',
            'page.is_positive_integer'          => '页码必须为整数！',
            'is_rec.required'                   => '是否推荐不能为空！',
            'is_rec.in'                         => '推荐在0～2之间！',
            'images.required'                   => '图片不能为空！',
            'videos.required'                   => '视频封面不能为空！',
        ];
    }

    public function scene()
    {
        return [
            'create'        => ['lotteryType', 'title', 'content', 'type', 'images','videos'],
            'list'          => ['lotteryType', 'page', 'is_rec', 'type'],
            'detail'        => ['id'],
            'follow'        => ['id'],
            'collect'       => ['id'],
            'forward'       => ['id'],
        ];
    }

}
