<?php

namespace Modules\Api\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Common\Requests\SceneValidator;

class ForecastRequest extends FormRequest
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
            'id'               => 'required|is_positive_integer',  // 竞猜表id
            'bet_id'           => 'required|is_positive_integer',  // 竞猜表id
            'forecast_bet_id'  => 'required|is_positive_integer',  // 竞猜表id
            'forecast_type_id' => 'required|is_positive_integer',  // 竞猜表类型id
            'pic_detail_id'    => "required|is_positive_integer",
            'lotteryType'      => "required|" . Rule::in([1, 2, 5, 6, 7]),
            'bet_list'         => "required",
            'each_bet_money'   => "required|numeric",
            'bet_money'        => "required|numeric",
            'year'             => "required|is_positive_integer",
            'issue'            => "required|is_positive_integer",
            'body'             => "required",
            'content'          => "required|array",
            'vote_zodiac'      => "required|" . Rule::in(range(1, 12)),
            'page'             => 'required|is_positive_integer',
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
            'id.required'                          => '竞猜Id不能为空！',
            'id.is_positive_integer'               => '竞猜Id必须为整数！',
            'bet_id.required'                      => '竞猜Id不能为空！',
            'bet_id.is_positive_integer'           => '竞猜Id必须为整型数组！',
            'forecast_bet_id.required'             => '竞猜Id不能为空！',
            'forecast_bet_id.is_positive_integer'  => '竞猜Id必须为整数！',
            'forecast_type_id.required'            => '竞猜类型Id不能为空！',
            'forecast_type_id.is_positive_integer' => '竞猜类型Id必须为整数！',
            'pic_detail_id.required'               => '图片ID不能为空！',
            'pic_detail_id.is_positive_integer'    => '图片ID应为整数！',
            'year.required'                        => '年份不能为空！',
            'year.is_positive_integer'             => '年份必须为整数！',
            'lotteryType.required'                 => '彩票分类不能为空！',
            'lotteryType.in'                       => '彩票分类在1~5之间！',
            'bet_list.required'                    => '投注参数不能为空！',
            'each_bet_money.required'              => '每一注金额不能为空！',
            'bet_money.required'                   => '总投注金额不能为空！',
            'vote_zodiac.required'                 => '生肖不能为空！',
            'vote_zodiac.in'                       => '生肖字段应在1~12之间！',
            'body.required'                        => '请输入标题！',
            'content.required'                     => '请输入竞猜内容！',
            'content.array'                        => '竞猜内容必须是数组！',
            'page.required'                        => '页码不能为空！',
            'page.is_positive_integer'             => '页码必须为整数！',
        ];
    }

    public function scene()
    {
        return [
            'create' => ['forecast_type_id', 'pic_detail_id', 'body', 'content'],
            'list'   => ['pic_detail_id', 'page'],
            'detail' => ['id'],  // 用户竞猜id
            'follow' => ['id'],
            'bet'    => ['lotteryType', 'forecast_bet_id', 'bet_list', 'each_bet_money', 'bet_money'],
            'cancel' => ['bet_id', 'lotteryType'],
        ];
    }

}
