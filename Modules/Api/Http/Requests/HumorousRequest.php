<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class HumorousRequest extends FormRequest
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
            'id'                        => 'required|is_positive_integer',  // 幽默竞猜表主键id
            'guessId'                   => 'required|is_positive_integer',  // 幽默竞猜表竞猜id
            'lotteryType'               => "required|".Rule::in([1, 2, 3, 4, 5, 6, 7]),
            'year'                      => "required|is_positive_integer",
            'issue'                     => "required|is_positive_integer",
            'vote_zodiac'               => "required|".Rule::in(['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪']),
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
            'id.is_positive_integer'            => 'ID必须为整数！',
            'guessId.required'                  => 'guessId不能为空！',
            'guessId.is_positive_integer'       => 'guessId必须为整数！',
            'page.required'                     => '页码不能为空！',
            'page.is_positive_integer'          => '页码必须为整数！',
            'year.required'                     => '年份不能为空！',
            'year.is_positive_integer'          => '年份必须为整数！',
            'lotteryType.required'              => '彩票分类不能为空！',
            'lotteryType.in'                    => '彩票分类在1~5之间！',
            'vote_zodiac.required'              => '生肖不能为空！',
            'vote_zodiac.in'                    => '生肖字段不正确！',
        ];
    }

    public function scene()
    {
        return [
            'guess'       => ['year', 'lotteryType'],
            'detail'      => ['id'],
            'follow'      => ['id'],
            'collect'     => ['id'],
            'vote'        => ['id', 'vote_zodiac'],
        ];
    }

}
