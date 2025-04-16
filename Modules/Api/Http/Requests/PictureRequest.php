<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class PictureRequest extends FormRequest
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
            'id'            => 'required|is_positive_integer',  // 图片详情表主键id
            'page'          => 'required|is_positive_integer',
            'lotteryType'   => "required|" . Rule::in([1, 2, 3, 4, 5, 6, 7]),
            'year'          => "required|is_positive_integer",
            'color'         => "required|" . Rule::in([1, 2]),
            'pictureTypeId' => "required|is_positive_integer",
            'pictureId'     => "required|is_positive_integer",
            'issue'     => "required|numeric",
            'pictureName'     => "required",
            'vote_zodiac'   => "required|" . Rule::in([
                    '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'
                ]),
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
            'id.required'                       => 'ID不能为空！',
            'id.is_positive_integer'            => 'ID必须为整数！',
            'page.required'                     => '页码不能为空！',
            'page.is_positive_integer'          => '页码必须为整数！',
            'lotteryType.required'              => '彩票分类不能为空！',
            'lotteryType.in'                    => '彩票ID不存在！',
            'year.required'                     => '年份不能为空！',
            'year.is_positive_integer'          => '年份格式应为整数！',
            'color.required'                    => '颜色不能为空！',
            'color.in'                          => '颜色在1~2之间！',
            'pictureTypeId.required'            => '图片分类ID不能为空！',
            'pictureTypeId.is_positive_integer' => '图片分类ID应为整数！',
            'pictureId.required'                => '图片ID不能为空！',
            'pictureId.is_positive_integer'     => '图片ID应为整数！',
            'vote_zodiac.required'              => '生肖不能为空！',
            'vote_zodiac.in'                    => '生肖字段不正确！',
        ];
    }

    public function scene()
    {
        return [
            'index'         => ['page', 'lotteryType'],
            'cate'          => ['year', 'color', 'lotteryType'],
            'detail'        => ['pictureTypeId', 'year', 'lotteryType'],
            'details'       => ['pictureTypeId', 'lotteryType'],
            'issues'        => ['pictureTypeId', 'year'],
            'vote'          => ['pictureId', 'vote_zodiac'],
            'follow'        => ['pictureId'],
            'flow_follow'        => ['pictureId', 'lotteryType', 'year', 'pictureTypeId', 'pictureName', 'color', 'issue'],
            'collect'       => ['pictureId'],
            'recommend'     => ['pictureTypeId', 'year', 'page', 'lotteryType'],
            'series_detail' => ['id'],
            'video' => ['lotteryType'],
            'ai_analyze' => ['lotteryType', 'pictureTypeId', 'year'],
        ];
    }
}
